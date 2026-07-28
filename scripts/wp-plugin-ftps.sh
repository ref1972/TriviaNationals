#!/usr/bin/env bash
# Deploy helper for the live Trivia Nationals "Event Schedule Manager" plugin.
# Usage: scripts/wp-plugin-ftps.sh pull|diff|deploy
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

FTP_HOST="wartburg.websitewelcome.com"
FTP_PORT=21
FTP_USER="tndeploy@trivianationals.org"
KEYCHAIN_SERVICE="ftp.trivianationals.org"
REMOTE_PATH="plugins/trivia-desc-editor-restored/trivia-desc-editor.php"
LOCAL_PATH="$REPO_ROOT/trivia-desc-editor-restored/trivia-desc-editor.php"
STATE_FILE="$REPO_ROOT/.wp-deploy-state/trivia-desc-editor.sha256"
BACKUP_DIR="$REPO_ROOT/backups/trivia-desc-editor"

TMP_FILES=()
cleanup() {
	local f
	for f in "${TMP_FILES[@]:-}"; do
		[[ -n "$f" && -f "$f" ]] && rm -f "$f"
	done
	return 0
}
trap 'ec=$?; cleanup; exit $ec' EXIT

mktemp_tracked() {
	local f
	f="$(mktemp)"
	TMP_FILES+=("$f")
	printf '%s' "$f"
}

get_password() {
	if [[ -n "${WP_FTP_PASSWORD:-}" ]]; then
		printf '%s' "$WP_FTP_PASSWORD"
		return
	fi
	if command -v security >/dev/null 2>&1; then
		local pw
		pw="$(security find-internet-password -a "$FTP_USER" -s "$KEYCHAIN_SERVICE" -w 2>/dev/null || true)"
		if [[ -n "$pw" ]]; then
			printf '%s' "$pw"
			return
		fi
	fi
	echo "error: no FTP password found. Set WP_FTP_PASSWORD or store one in macOS Keychain:" >&2
	echo "  security add-internet-password -a \"$FTP_USER\" -s \"$KEYCHAIN_SERVICE\" -w" >&2
	exit 1
}

# Writes host/login/password into a 600-permission netrc file so credentials
# never appear in argv (visible to `ps`) or in shell history.
make_netrc() {
	local netrc pw
	netrc="$(mktemp_tracked)"
	pw="$(get_password)"
	chmod 600 "$netrc"
	{
		printf 'machine %s\n' "$FTP_HOST"
		printf 'login %s\n' "$FTP_USER"
		printf 'password %s\n' "$pw"
	} >"$netrc"
	printf '%s' "$netrc"
}

ftp_url() {
	printf 'ftp://%s:%s/%s' "$FTP_HOST" "$FTP_PORT" "$REMOTE_PATH"
}

fetch_live() {
	local dest="$1" netrc
	netrc="$(make_netrc)"
	# --tlsv1.2 --tls-max 1.2: this host's FTPS data channel has intermittently
	# aborted TLS 1.3 transfers with "451 Error during read from data
	# connection" (seen 2026-07-28, mid-transfer on a completed upload).
	# Forcing 1.2 avoids the server-side bug.
	curl -sS --ssl-reqd --tlsv1.2 --tls-max 1.2 --netrc-file "$netrc" "$(ftp_url)" -o "$dest"
}

push_live() {
	local src="$1" netrc
	netrc="$(make_netrc)"
	curl -sS --ssl-reqd --tlsv1.2 --tls-max 1.2 --netrc-file "$netrc" -T "$src" "$(ftp_url)"
}

sha256_of() {
	shasum -a 256 "$1" | awk '{print $1}'
}

cmd_pull() {
	local force=0
	if [[ "${1:-}" == "--force" ]]; then
		force=1
	fi

	if [[ $force -ne 1 && -f "$LOCAL_PATH" ]]; then
		local dirty
		dirty="$(git -C "$REPO_ROOT" status --porcelain -- "$LOCAL_PATH" 2>/dev/null || true)"
		if [[ -n "$dirty" ]]; then
			echo "error: local working copy has uncommitted changes, refusing to overwrite it:" >&2
			echo "$dirty" >&2
			echo "Commit or stash your changes first, or re-run as 'pull --force' to overwrite anyway (a backup will still be made)." >&2
			exit 1
		fi
	fi

	local remote_tmp
	remote_tmp="$(mktemp_tracked)"
	echo "Pulling live file from $FTP_HOST ..."
	fetch_live "$remote_tmp"

	if [[ -f "$LOCAL_PATH" ]] && diff -q "$LOCAL_PATH" "$remote_tmp" >/dev/null 2>&1; then
		echo "No changes: local working copy already matches live."
	else
		echo "Live differs from local working copy. Updating local copy:"
		diff -u "$LOCAL_PATH" "$remote_tmp" || true
		if [[ -f "$LOCAL_PATH" ]]; then
			mkdir -p "$BACKUP_DIR"
			local local_backup="$BACKUP_DIR/trivia-desc-editor.php.pre-pull.$(date +%Y%m%d-%H%M%S).bak"
			cp "$LOCAL_PATH" "$local_backup"
			echo "Backed up previous local copy to $local_backup"
		fi
		cp "$remote_tmp" "$LOCAL_PATH"
	fi

	mkdir -p "$(dirname "$STATE_FILE")"
	sha256_of "$remote_tmp" >"$STATE_FILE"
	echo "Recorded reviewed baseline: $(cat "$STATE_FILE")"
}

cmd_diff() {
	local remote_tmp
	remote_tmp="$(mktemp_tracked)"
	echo "Fetching live file from $FTP_HOST for comparison ..."
	fetch_live "$remote_tmp"

	if diff -q "$LOCAL_PATH" "$remote_tmp" >/dev/null 2>&1; then
		echo "No differences between local working copy and live."
		exit 0
	else
		diff -u "$remote_tmp" "$LOCAL_PATH"
		exit 1
	fi
}

cmd_deploy() {
	echo "Linting local file ..."
	php -l "$LOCAL_PATH"

	if [[ ! -f "$STATE_FILE" ]]; then
		echo "error: no reviewed baseline recorded. Run 'pull' or 'diff' first." >&2
		exit 1
	fi
	local expected_hash
	expected_hash="$(cat "$STATE_FILE")"

	local remote_tmp remote_hash
	remote_tmp="$(mktemp_tracked)"
	echo "Re-checking live file hasn't changed since last reviewed pull ..."
	fetch_live "$remote_tmp"
	remote_hash="$(sha256_of "$remote_tmp")"

	if [[ "$remote_hash" != "$expected_hash" ]]; then
		echo "error: live file has changed since the last reviewed pull (likely a direct wp-admin edit)." >&2
		echo "  expected: $expected_hash" >&2
		echo "  live now: $remote_hash" >&2
		echo "Run 'pull' or 'diff' to review the new live state before deploying." >&2
		exit 1
	fi
	echo "Live matches last reviewed pull. Proceeding."

	mkdir -p "$BACKUP_DIR"
	local backup_path="$BACKUP_DIR/trivia-desc-editor.php.$(date +%Y%m%d-%H%M%S).bak"
	cp "$remote_tmp" "$backup_path"
	echo "Backed up pre-deploy live file to $backup_path"

	echo "Uploading local file to $FTP_HOST ..."
	push_live "$LOCAL_PATH"

	local verify_tmp verify_hash local_hash
	verify_tmp="$(mktemp_tracked)"
	echo "Verifying deployed content ..."
	fetch_live "$verify_tmp"
	verify_hash="$(sha256_of "$verify_tmp")"
	local_hash="$(sha256_of "$LOCAL_PATH")"

	if [[ "$verify_hash" != "$local_hash" ]]; then
		echo "error: post-deploy verification failed — live content does not match local file." >&2
		echo "  local:    $local_hash" >&2
		echo "  live now: $verify_hash" >&2
		exit 1
	fi

	echo "$verify_hash" >"$STATE_FILE"
	echo "Deploy verified. SHA-256 $verify_hash now live and recorded as the new reviewed baseline."
}

case "${1:-}" in
pull)
	shift
	cmd_pull "$@"
	;;
diff) cmd_diff ;;
deploy) cmd_deploy ;;
*)
	echo "Usage: $(basename "$0") pull [--force]|diff|deploy" >&2
	exit 1
	;;
esac
