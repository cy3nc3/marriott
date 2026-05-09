#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/home/deploy/marriott"
cd "$APP_DIR"

set -a
source .env
set +a

if [[ "${BACKUP_ALERT_ENABLED:-false}" != "true" ]]; then
  echo "Backup alerting is disabled."
  exit 0
fi

if [[ -z "${RESEND_API_KEY:-}" || -z "${BACKUP_ALERT_TO:-}" ]]; then
  echo "RESEND_API_KEY and BACKUP_ALERT_TO are required for backup alerting."
  exit 1
fi

MAIL_FROM="${MAIL_FROM_ADDRESS:-no-reply@mail.msqc.tech}"
MAIL_FROM_NAME="${MAIL_FROM_NAME:-MarriottConnect}"
HOSTNAME_VALUE="$(hostname -f 2>/dev/null || hostname)"
NOW_UTC="$(date -u '+%Y-%m-%d %H:%M:%S UTC')"
SUBJECT="[ALERT] Marriott backup failed on ${HOSTNAME_VALUE}"
TEXT_BODY="Backup failure detected on ${HOSTNAME_VALUE} at ${NOW_UTC}.

Please investigate:
1. sudo systemctl status --no-pager --lines=120 marriott-backup.service
2. sudo journalctl -u marriott-backup.service -n 200 --no-pager
3. sudo systemctl start marriott-backup.service
"
TEXT_BODY="$(echo "$TEXT_BODY" | tr '\n' ' ' | sed 's/  */ /g')"

curl --fail --silent --show-error https://api.resend.com/emails \
  -H "Authorization: Bearer ${RESEND_API_KEY}" \
  -H "Content-Type: application/json" \
  --data "{
    \"from\": \"${MAIL_FROM_NAME} <${MAIL_FROM}>\",
    \"to\": [\"${BACKUP_ALERT_TO}\"],
    \"subject\": \"${SUBJECT}\",
    \"text\": \"${TEXT_BODY}\"
  }" >/dev/null

echo "Backup failure alert sent to ${BACKUP_ALERT_TO}."
