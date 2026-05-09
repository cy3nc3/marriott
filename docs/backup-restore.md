# Backup And Restore (Droplet Demo Environment)

## Scope

- Database backup: DigitalOcean Managed PostgreSQL (`marriott_prod`)
- File backup: `storage/app` (public + private uploaded artifacts)
- Backup location: `/home/deploy/backups/marriott`
- Off-server copy: DigitalOcean Spaces (`marriott-bucket-private/marriott/backups`)

## Create Backup Manually

```bash
cd /home/deploy/marriott
./scripts/ops/backup.sh
```

Outputs:
- `db-<timestamp>.sql.gz`
- `storage-<timestamp>.tar.gz`
- `manifest-<timestamp>.txt`
- Uploaded copies of the same artifacts to Spaces when backup env vars are configured.

Retention:
- 14 days by default (`RETENTION_DAYS` can override).

## Restore Database

```bash
cd /home/deploy/marriott
./scripts/ops/restore-db.sh /home/deploy/backups/marriott/db-<timestamp>.sql.gz
```

Notes:
- Restore prompts for explicit `YES`.
- Restore targets current `.env` database connection.

## Restore Storage Files

```bash
tar -xzf /home/deploy/backups/marriott/storage-<timestamp>.tar.gz -C /home/deploy/marriott
```

## Systemd Automation

- Service: `marriott-backup.service`
- Timer: `marriott-backup.timer`
- Schedule: daily
- Failure alert service: `marriott-backup-failure-alert.service` (triggered by `OnFailure`)

## Spaces Environment Variables

The backup script reads these values from `.env`:

- `BACKUP_SPACES_ACCESS_KEY_ID`
- `BACKUP_SPACES_SECRET_ACCESS_KEY`
- `BACKUP_SPACES_REGION`
- `BACKUP_SPACES_ENDPOINT` (example: `https://sgp1.digitaloceanspaces.com`)
- `BACKUP_SPACES_BUCKET`
- `BACKUP_SPACES_PREFIX`

If `BACKUP_SPACES_BUCKET` is set, backup upload to Spaces is required and failures will fail the backup job.

## Failure Alert Variables

- `BACKUP_ALERT_ENABLED=true`
- `BACKUP_ALERT_TO=<alert recipient email>`

Alert mail uses:
- `RESEND_API_KEY`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

Useful commands:

```bash
systemctl status marriott-backup.timer
systemctl status marriott-backup.service
systemctl status marriott-backup-failure-alert.service
sudo journalctl -u marriott-backup.service -n 100 --no-pager
sudo journalctl -u marriott-backup-failure-alert.service -n 100 --no-pager
sudo systemctl start marriott-backup-failure-alert.service
```
