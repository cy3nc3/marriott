#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/home/deploy/marriott"
BACKUP_ROOT="/home/deploy/backups/marriott"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

cd "$APP_DIR"

set -a
source .env
set +a

if [[ "${DB_CONNECTION:-}" != "pgsql" ]]; then
  echo "DB_CONNECTION is '${DB_CONNECTION:-unset}'. Refusing backup: expected 'pgsql'."
  exit 1
fi

if command -v pg_dump-18 >/dev/null 2>&1; then
  PG_DUMP_BIN="pg_dump-18"
elif command -v pg_dump >/dev/null 2>&1; then
  PG_DUMP_BIN="pg_dump"
else
  echo "pg_dump is not available. Install postgresql-client first."
  exit 1
fi

TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$BACKUP_ROOT"

DB_DUMP_FILE="$BACKUP_ROOT/db-${TIMESTAMP}.sql.gz"
FILES_DUMP_FILE="$BACKUP_ROOT/storage-${TIMESTAMP}.tar.gz"
MANIFEST_FILE="$BACKUP_ROOT/manifest-${TIMESTAMP}.txt"

DB_CONN_STRING="${DB_URL:-}"
if [[ -z "$DB_CONN_STRING" ]]; then
  DB_CONN_STRING="postgresql://${DB_USERNAME}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_DATABASE}?sslmode=${DB_SSLMODE:-require}"
fi

SPACES_ACCESS_KEY_ID="${BACKUP_SPACES_ACCESS_KEY_ID:-${AWS_ACCESS_KEY_ID:-}}"
SPACES_SECRET_ACCESS_KEY="${BACKUP_SPACES_SECRET_ACCESS_KEY:-${AWS_SECRET_ACCESS_KEY:-}}"
SPACES_REGION="${BACKUP_SPACES_REGION:-${AWS_DEFAULT_REGION:-}}"
SPACES_ENDPOINT="${BACKUP_SPACES_ENDPOINT:-${AWS_ENDPOINT:-}}"
SPACES_BUCKET="${BACKUP_SPACES_BUCKET:-}"
SPACES_PREFIX="${BACKUP_SPACES_PREFIX:-marriott/backups}"

if [[ -z "$SPACES_ENDPOINT" && -n "$SPACES_REGION" ]]; then
  SPACES_ENDPOINT="https://${SPACES_REGION}.digitaloceanspaces.com"
fi

echo "Creating PostgreSQL dump..."
DB_DUMP_TMP="$(mktemp "$BACKUP_ROOT/.db-${TIMESTAMP}.XXXXXX.sql.gz")"
FILES_DUMP_TMP="$(mktemp "$BACKUP_ROOT/.storage-${TIMESTAMP}.XXXXXX.tar.gz")"
MANIFEST_TMP="$(mktemp "$BACKUP_ROOT/.manifest-${TIMESTAMP}.XXXXXX.txt")"
S3CFG_TMP="$(mktemp "$BACKUP_ROOT/.s3cfg-${TIMESTAMP}.XXXXXX")"

cleanup() {
  rm -f "$DB_DUMP_TMP" "$FILES_DUMP_TMP" "$MANIFEST_TMP" "$S3CFG_TMP"
}
trap cleanup EXIT

"$PG_DUMP_BIN" "$DB_CONN_STRING" | gzip -9 > "$DB_DUMP_TMP"
mv "$DB_DUMP_TMP" "$DB_DUMP_FILE"

echo "Archiving storage files..."
tar -czf "$FILES_DUMP_TMP" -C "$APP_DIR" storage/app
mv "$FILES_DUMP_TMP" "$FILES_DUMP_FILE"

chmod 600 "$DB_DUMP_FILE" "$FILES_DUMP_FILE"

{
  echo "timestamp_utc=${TIMESTAMP}"
  echo "app_dir=${APP_DIR}"
  echo "db_connection=${DB_CONNECTION}"
  echo "db_host=${DB_HOST}"
  echo "db_port=${DB_PORT}"
  echo "db_database=${DB_DATABASE}"
  echo "files_archive=storage/app"
  echo
  sha256sum "$DB_DUMP_FILE"
  sha256sum "$FILES_DUMP_FILE"
} > "$MANIFEST_TMP"
mv "$MANIFEST_TMP" "$MANIFEST_FILE"
chmod 600 "$MANIFEST_FILE"

echo "Applying retention policy: ${RETENTION_DAYS} days..."
find "$BACKUP_ROOT" -type f -name "db-*.sql.gz" -mtime "+${RETENTION_DAYS}" -delete
find "$BACKUP_ROOT" -type f -name "storage-*.tar.gz" -mtime "+${RETENTION_DAYS}" -delete
find "$BACKUP_ROOT" -type f -name "manifest-*.txt" -mtime "+${RETENTION_DAYS}" -delete

if [[ -n "$SPACES_BUCKET" ]]; then
  if [[ -z "$SPACES_ACCESS_KEY_ID" || -z "$SPACES_SECRET_ACCESS_KEY" || -z "$SPACES_ENDPOINT" ]]; then
    echo "Spaces upload requested but credentials/endpoint are incomplete."
    exit 1
  fi
  if ! command -v s3cmd >/dev/null 2>&1; then
    echo "s3cmd is not available. Install s3cmd first."
    exit 1
  fi

  SPACES_HOST="${SPACES_ENDPOINT#https://}"
  SPACES_HOST="${SPACES_HOST#http://}"
  SPACES_PREFIX="${SPACES_PREFIX#/}"
  SPACES_PREFIX="${SPACES_PREFIX%/}"

  cat > "$S3CFG_TMP" <<EOF
[default]
access_key = $SPACES_ACCESS_KEY_ID
secret_key = $SPACES_SECRET_ACCESS_KEY
host_base = $SPACES_HOST
host_bucket = %(bucket)s.$SPACES_HOST
use_https = True
signature_v2 = False
EOF
  chmod 600 "$S3CFG_TMP"

  for artifact in "$DB_DUMP_FILE" "$FILES_DUMP_FILE" "$MANIFEST_FILE"; do
    artifact_name="$(basename "$artifact")"
    artifact_key="$artifact_name"
    if [[ -n "$SPACES_PREFIX" ]]; then
      artifact_key="${SPACES_PREFIX}/${artifact_name}"
    fi
    echo "Uploading $artifact_name to s3://${SPACES_BUCKET}/${artifact_key}..."
    s3cmd -c "$S3CFG_TMP" put "$artifact" "s3://${SPACES_BUCKET}/${artifact_key}"
  done
fi

echo "Backup complete:"
echo "  DB:      $DB_DUMP_FILE"
echo "  Storage: $FILES_DUMP_FILE"
echo "  Manifest:$MANIFEST_FILE"
if [[ -n "$SPACES_BUCKET" ]]; then
  echo "  Spaces:  s3://${SPACES_BUCKET}/${SPACES_PREFIX}"
fi

trap - EXIT
