#!/bin/bash

CRON_JOB="0 2 * * * curl -fsS https://crm.flipperschool.com/admin/clear_logs.php >/dev/null 2>&1"
CRON_EXISTS=$(crontab -l 2>/dev/null | grep -F "$CRON_JOB")
# Check if the cron job already exists

if [ -z "$CRON_EXISTS" ]; then
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo "Cron job added: $CRON_JOB"
else
    echo "Cron job already exists."
fi
