#!/usr/bin/env bash
set -euo pipefail

database_path="${DATABASE_PATH:-/var/lib/trivia-mail-relay/audit.sqlite}"
backup_directory="${BACKUP_DIRECTORY:-/var/backups/trivia-mail-relay}"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
destination="${backup_directory}/audit-${timestamp}.sqlite.gz"

install -d -m 0700 "$backup_directory"
temporary="$(mktemp "${backup_directory}/.audit-${timestamp}.XXXXXX.sqlite")"
trap 'rm -f "$temporary"' EXIT
sqlite3 "$database_path" ".backup '$temporary'"
sqlite3 "$temporary" "PRAGMA integrity_check;" | grep -qx ok
gzip -c "$temporary" > "$destination"
chmod 0600 "$destination"
find "$backup_directory" -type f -name 'audit-*.sqlite.gz' -mtime +30 -delete
printf '%s\n' "$destination"
