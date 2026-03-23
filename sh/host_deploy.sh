#!/bin/bash
# host_deploy.sh - ホスト側でcrontabを生成し CRONTAB_DEST へ直接デプロイする
# systemd webcron-deploy.service または deploy_cron.sh から呼ばれる

set -eu

# 設定読み込み
SCRIPT_DIR="$(dirname "$(readlink -f "$0")")"
. "$SCRIPT_DIR/../conf/env.sh"

GENERATOR="$PHP_SCRIPT_BASE/php/crontab_generator.php"
TRIGGER_FILE="$DATA_DIR/.deploy_trigger"
TEMP_FILE=$(mktemp)

# crontab生成 (コンテナ有無を吸収)
if [ -n "$CONTAINER" ]; then
    sudo -u "$CONTAINER_USER" podman exec "$CONTAINER" "$PHP_BIN" "$GENERATOR" > "$TEMP_FILE" 2>&1
else
    "$PHP_BIN" "$GENERATOR" > "$TEMP_FILE" 2>&1
fi

if [ ! -s "$TEMP_FILE" ]; then
    echo "Error: Generated cron content is empty." >&2
    rm -f "$TEMP_FILE"
    exit 1
fi

# crontab 出力先へ反映
mv "$TEMP_FILE" "$CRONTAB_DEST"
chmod 644 "$CRONTAB_DEST"

# crond に即時再読み込みさせる
if [ -f /var/run/crond.pid ]; then
    kill -HUP "$(cat /var/run/crond.pid)" 2>/dev/null || true
fi

# トリガーファイルを削除 (存在する場合)
rm -f "$TRIGGER_FILE"

echo "Success: Deployed $CRONTAB_DEST"
