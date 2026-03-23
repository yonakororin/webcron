#!/bin/bash
# host_deploy.sh - ホスト側でcrontabを生成し /etc/cron.d/ へ直接デプロイする
# systemd webcron-deploy.service から呼ばれる (root権限で実行)

set -eu

# --- 設定 ---
CONTAINER="podman_php_1"
PHP_BIN="/usr/local/bin/php"
GENERATOR="/var/www/webcron/php/crontab_generator.php"
DEST_CRON="/etc/cron.d/web_cron_jobs"

# volume内パス (Crontabファイル閲覧タブ用にも書き込む)
VOLUME_DATA_DIR="/home/ubuntu/.local/share/containers/storage/volumes/podman_webcron-data/_data"
VOLUME_CRON_FILE="$VOLUME_DATA_DIR/web_cron_jobs"
TRIGGER_FILE="$VOLUME_DATA_DIR/.deploy_trigger"

TEMP_FILE=$(mktemp)

# crontab生成 (rootlessコンテナのためubuntuユーザーで実行)
sudo -u ubuntu podman exec "$CONTAINER" "$PHP_BIN" "$GENERATOR" > "$TEMP_FILE" 2>&1

if [ ! -s "$TEMP_FILE" ]; then
    echo "Error: Generated cron content is empty." >&2
    rm -f "$TEMP_FILE"
    exit 1
fi

# /etc/cron.d/ へ反映
mv "$TEMP_FILE" "$DEST_CRON"
chmod 644 "$DEST_CRON"

# volume内のファイルも更新 (Crontabファイル閲覧タブ用)
cp "$DEST_CRON" "$VOLUME_CRON_FILE"
chmod 644 "$VOLUME_CRON_FILE"

# crond に即時再読み込みさせる
if [ -f /var/run/crond.pid ]; then
    kill -HUP "$(cat /var/run/crond.pid)" 2>/dev/null || true
fi

# トリガーファイルを削除
rm -f "$TRIGGER_FILE"

echo "Success: Deployed $DEST_CRON"
