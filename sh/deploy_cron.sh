#!/bin/sh
set -eu

SCRIPT_DIR=$(dirname "$(readlink -f "$0")")
PHP_SCRIPT="$SCRIPT_DIR/../php/crontab_generator.php"
TEMP_FILE="/tmp/cron_export.tmp"
DEST_FILE="/var/www/webcron-data/web_cron_jobs"

# PHP実行
/usr/local/bin/php "$PHP_SCRIPT" > "$TEMP_FILE"

if [ ! -s "$TEMP_FILE" ]; then
    echo "Error: Generated cron content is empty."
    exit 1
fi

echo "Crontabの内容が変更されました。更新します。"

mv "$TEMP_FILE" "$DEST_FILE"
chmod 644 "$DEST_FILE"

# crondに設定を再読み込みさせる
if [ -f /var/run/crond.pid ]; then
    kill -HUP "$(cat /var/run/crond.pid)" 2>/dev/null || true
fi

echo "Success: Updated $DEST_FILE"
