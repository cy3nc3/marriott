#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Usage: $0 /path/to/db-YYYYMMDDTHHMMSSZ.sql.gz"
  exit 1
fi

DUMP_FILE="$1"
if [[ ! -f "$DUMP_FILE" ]]; then
  echo "Dump file not found: $DUMP_FILE"
  exit 1
fi

APP_DIR="/home/deploy/marriott"
cd "$APP_DIR"

set -a
source .env
set +a

if [[ "${DB_CONNECTION:-}" != "pgsql" ]]; then
  echo "DB_CONNECTION is '${DB_CONNECTION:-unset}'. Refusing restore: expected 'pgsql'."
  exit 1
fi

if ! command -v psql >/dev/null 2>&1; then
  echo "psql is not available. Install postgresql-client first."
  exit 1
fi

DB_CONN_STRING="${DB_URL:-}"
if [[ -z "$DB_CONN_STRING" ]]; then
  DB_CONN_STRING="postgresql://${DB_USERNAME}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_DATABASE}?sslmode=${DB_SSLMODE:-require}"
fi

echo "About to restore into database '${DB_DATABASE}' on host '${DB_HOST}'."
echo "This is destructive if target already has data."
read -r -p "Type YES to continue: " CONFIRM
if [[ "$CONFIRM" != "YES" ]]; then
  echo "Restore cancelled."
  exit 1
fi

echo "Restoring dump..."
gunzip -c "$DUMP_FILE" | psql "$DB_CONN_STRING"

echo "Restore complete."
