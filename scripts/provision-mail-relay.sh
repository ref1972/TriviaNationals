#!/usr/bin/env bash
set -euo pipefail

deploy_host="${MAIL_RELAY_DEPLOY_HOST:-root@137.184.62.161}"
deploy_ref="${MAIL_RELAY_DEPLOY_REF:-$(git rev-parse HEAD)}"
release_id="${MAIL_RELAY_RELEASE_ID:-mail-relay-${deploy_ref:0:12}}"
archive="$(mktemp /tmp/trivia-mail-relay.XXXXXX.tar.gz)"
remote_archive="/tmp/${release_id}.tar.gz"
trap 'rm -f "$archive"' EXIT

git archive --format=tar.gz --prefix=workspace-mail-relay/ -o "$archive" "$deploy_ref:workspace-mail-relay"
scp "$archive" "${deploy_host}:${remote_archive}"

ssh "$deploy_host" bash -s -- "$release_id" "$remote_archive" <<'REMOTE'
set -euo pipefail
release_id="$1"
remote_archive="$2"
release_dir="/var/www/trivia-mail-relay/releases/${release_id}"

test -s /etc/trivia-mail-relay-oauth-client.json
test -s /etc/trivia-mail-relay-oauth-token.json

if ! id trivia-mail-relay >/dev/null 2>&1; then
  useradd --system --home /var/lib/trivia-mail-relay --shell /usr/sbin/nologin trivia-mail-relay
fi
install -d -o root -g root -m 0755 /var/www/trivia-mail-relay/releases
install -d -o trivia-mail-relay -g trivia-mail-relay -m 0750 /var/lib/trivia-mail-relay
install -d -o root -g root -m 0700 /var/backups/trivia-mail-relay
rm -rf "$release_dir"
install -d -o root -g root -m 0755 "$release_dir"
tar -xzf "$remote_archive" -C "$release_dir" --strip-components=1
rm -f "$remote_archive"
cd "$release_dir"
PATH=/opt/timed-quiz-node/bin:/usr/bin:/bin /opt/timed-quiz-node/bin/npm ci --omit=dev

chown root:trivia-mail-relay /etc/trivia-mail-relay-oauth-client.json /etc/trivia-mail-relay-oauth-token.json
chmod 0640 /etc/trivia-mail-relay-oauth-client.json /etc/trivia-mail-relay-oauth-token.json

if [ ! -f /etc/trivia-mail-relay-timed-quiz.secret ]; then
  umask 077
  openssl rand -base64 48 | tr -d '\n' > /etc/trivia-mail-relay-timed-quiz.secret
  openssl rand -base64 48 | tr -d '\n' > /etc/trivia-mail-relay-trivia-nationals.secret
fi
timed_hash="$(sha256sum /etc/trivia-mail-relay-timed-quiz.secret | awk '{print $1}')"
wordpress_hash="$(sha256sum /etc/trivia-mail-relay-trivia-nationals.secret | awk '{print $1}')"

umask 027
cat > /etc/trivia-mail-relay.env <<ENV
PORT=8082
RELEASE_ID=${release_id}
DATABASE_PATH=/var/lib/trivia-mail-relay/audit.sqlite
GOOGLE_OAUTH_CLIENT_FILE=/etc/trivia-mail-relay-oauth-client.json
GOOGLE_OAUTH_TOKEN_FILE=/etc/trivia-mail-relay-oauth-token.json
MAIL_FROM_EMAIL=info@trivianationals.org
MAIL_FROM_NAME=Trivia Nationals
MAIL_REPLY_TO=info@trivianationals.org
DAILY_SAFETY_LIMIT=1000
HOURLY_SAFETY_LIMIT=300
MIN_SEND_INTERVAL_MS=3000
RELAY_CLIENTS=timed_quiz:${timed_hash},trivia_nationals:${wordpress_hash}
ENV
chown root:trivia-mail-relay /etc/trivia-mail-relay.env
chmod 0640 /etc/trivia-mail-relay.env

ln -sfn "$release_dir" /var/www/trivia-mail-relay/current
install -m 0644 deploy/trivia-mail-relay.service /etc/systemd/system/trivia-mail-relay.service
install -m 0644 deploy/trivia-mail-relay-backup.service /etc/systemd/system/trivia-mail-relay-backup.service
install -m 0644 deploy/trivia-mail-relay-backup.timer /etc/systemd/system/trivia-mail-relay-backup.timer
install -m 0644 deploy/mail.triviaworkshop.com.nginx /etc/nginx/sites-available/mail.triviaworkshop.com
ln -sfn /etc/nginx/sites-available/mail.triviaworkshop.com /etc/nginx/sites-enabled/mail.triviaworkshop.com

systemctl daemon-reload
systemctl enable --now trivia-mail-relay.service trivia-mail-relay-backup.timer
systemctl restart trivia-mail-relay.service
nginx -t
systemctl reload nginx

for attempt in 1 2 3 4 5 6 7 8 9 10; do
  if curl -fsS http://127.0.0.1:8082/health; then break; fi
  if [ "$attempt" = 10 ]; then
    systemctl status trivia-mail-relay.service --no-pager -l
    exit 1
  fi
  sleep 1
done
timed_secret="$(cat /etc/trivia-mail-relay-timed-quiz.secret)"
curl -fsS -X POST http://127.0.0.1:8082/v1/mail \
  -H 'Content-Type: application/json' \
  -H 'X-Relay-Client: timed_quiz' \
  -H "Authorization: Bearer ${timed_secret}" \
  --data '{"action":"verify"}'
REMOTE

ssh "$deploy_host" "certbot --nginx -d mail.triviaworkshop.com --non-interactive --agree-tos --redirect --email info@trivianationals.org && systemctl start trivia-mail-relay-backup.service"
curl -fsS https://mail.triviaworkshop.com/health
status="$(curl -sS -o /dev/null -w '%{http_code}' -X POST https://mail.triviaworkshop.com/v1/mail -H 'Content-Type: application/json' --data '{"action":"verify"}')"
test "$status" = "401"
curl -fsS https://bee.triviaworkshop.com/health
curl -fsSI https://cass.triviaworkshop.com/ | head -1
