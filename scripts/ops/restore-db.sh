#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<EOF
Usage: $0 [--wipe-target] [--allow-nonempty] /path/to/db-YYYYMMDDTHHMMSSZ.sql.gz

Options:
  --wipe-target      Drop and recreate public schema before restore (recommended for retry-safe restore).
  --allow-nonempty   Allow restore even if target DB has existing rows.
EOF
}

WIPE_TARGET=0
ALLOW_NONEMPTY=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wipe-target)
      WIPE_TARGET=1
      shift
      ;;
    --allow-nonempty)
      ALLOW_NONEMPTY=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      break
      ;;
  esac
done

if [[ $# -ne 1 ]]; then
  usage
  exit 1
fi

DUMP_FILE="$1"
if [[ ! -f "$DUMP_FILE" ]]; then
  echo "Dump file not found: $DUMP_FILE"
  exit 1
fi

APP_DIR="/home/deploy/marriott"
LOCK_FILE="/tmp/marriott-restore.lock"
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
if ! command -v sha256sum >/dev/null 2>&1; then
  echo "sha256sum is not available."
  exit 1
fi

exec 9>"$LOCK_FILE"
if ! flock -n 9; then
  echo "Another restore is already running. Exiting."
  exit 1
fi

DB_CONN_STRING="${DB_URL:-}"
if [[ -z "$DB_CONN_STRING" ]]; then
  DB_CONN_STRING="postgresql://${DB_USERNAME}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_DATABASE}?sslmode=${DB_SSLMODE:-require}"
fi

MANIFEST_FILE="$(dirname "$DUMP_FILE")/manifest-$(basename "$DUMP_FILE" | sed -E 's/^db-(.+)\.sql\.gz$/\1/').txt"
if [[ -f "$MANIFEST_FILE" ]]; then
  echo "Verifying dump checksum using manifest: $MANIFEST_FILE"
  EXPECTED_HASH="$(awk -v f="$DUMP_FILE" '$2==f{print $1}' "$MANIFEST_FILE")"
  if [[ -n "$EXPECTED_HASH" ]]; then
    ACTUAL_HASH="$(sha256sum "$DUMP_FILE" | awk '{print $1}')"
    if [[ "$EXPECTED_HASH" != "$ACTUAL_HASH" ]]; then
      echo "Checksum mismatch for $DUMP_FILE"
      exit 1
    fi
  else
    echo "No matching checksum entry found for dump in manifest."
    exit 1
  fi
else
  echo "Warning: manifest not found beside dump. Continuing without checksum validation."
fi

NONEMPTY_COUNT="$(psql "$DB_CONN_STRING" -At -c "select count(*) from information_schema.tables where table_schema='public' and table_type='BASE TABLE' and (select coalesce(sum(reltuples)::bigint,0) from pg_class c join pg_namespace n on n.oid=c.relnamespace where n.nspname='public' and c.relname=table_name) > 0;")"
NONEMPTY_COUNT="${NONEMPTY_COUNT:-0}"
if [[ "$ALLOW_NONEMPTY" -ne 1 && "$WIPE_TARGET" -ne 1 && "$NONEMPTY_COUNT" -gt 0 ]]; then
  echo "Target database appears non-empty ($NONEMPTY_COUNT populated tables)."
  echo "Use --wipe-target for safe retry restore or --allow-nonempty to override."
  exit 1
fi

echo "About to restore into database '${DB_DATABASE}' on host '${DB_HOST}'."
if [[ "$WIPE_TARGET" -eq 1 ]]; then
  echo "Public schema will be dropped and recreated before restore."
fi
read -r -p "Type YES to continue: " CONFIRM
if [[ "$CONFIRM" != "YES" ]]; then
  echo "Restore cancelled."
  exit 1
fi

if [[ "$WIPE_TARGET" -eq 1 ]]; then
  echo "Wiping target public schema..."
  psql "$DB_CONN_STRING" -v ON_ERROR_STOP=1 -c "DROP SCHEMA IF EXISTS public CASCADE; CREATE SCHEMA public;"
fi

echo "Restoring dump..."
gunzip -c "$DUMP_FILE" | psql "$DB_CONN_STRING" -v ON_ERROR_STOP=1 -1

echo "Running post-restore integrity checks..."
psql "$DB_CONN_STRING" -v ON_ERROR_STOP=1 -c "
DO \$\$
DECLARE
  dup_enrollments bigint;
  dup_scores bigint;
  orphan_enrollments bigint;
BEGIN
  SELECT count(*) INTO dup_enrollments
  FROM (
    SELECT student_id, academic_year_id
    FROM enrollments
    GROUP BY student_id, academic_year_id
    HAVING count(*) > 1
  ) x;

  SELECT count(*) INTO dup_scores
  FROM (
    SELECT student_id, graded_activity_id
    FROM student_scores
    GROUP BY student_id, graded_activity_id
    HAVING count(*) > 1
  ) y;

  SELECT count(*) INTO orphan_enrollments
  FROM enrollments e
  LEFT JOIN students s ON s.id = e.student_id
  WHERE s.id IS NULL;

  IF dup_enrollments > 0 OR dup_scores > 0 OR orphan_enrollments > 0 THEN
    RAISE EXCEPTION 'Integrity check failed (dup_enrollments=%, dup_scores=%, orphan_enrollments=%)', dup_enrollments, dup_scores, orphan_enrollments;
  END IF;
END
\$\$;
"

echo "Restore complete and integrity checks passed."
