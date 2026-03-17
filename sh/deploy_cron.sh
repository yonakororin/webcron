#!/bin/bash
set -euo pipefail

# このスクリプト(sh/deploy_cron.sh)のあるディレクトリを取得
SCRIPT_DIR=$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")

# ★修正: 親ディレクトリ(../)に上がり、そこから php/ ディレクトリを参照する
PHP_SCRIPT="$SCRIPT_DIR/../php/crontab_generator.php"

# 一時ファイル等は sh/ ディレクトリ内に作るか、ルートに作るか任意ですが
# ここではスクリプトと同じ場所(sh/)に作ります
TEMP_FILE="$SCRIPT_DIR/cron_export.tmp"
DEST_FILE="/etc/cron.d/web_cron_jobs"

# PHP実行
/usr/bin/php "$PHP_SCRIPT" > "$TEMP_FILE"

if [ ! -s "$TEMP_FILE" ]; then
    echo "Error: Generated cron content is empty."
    exit 1
fi

# 3. 内容が異なる場合のみ、crontabを新しい内容で書き換える
echo "Crontabの内容が変更されました。更新します。"

sudo mv ${TEMP_FILE} ${DEST_FILE}
sudo chmod 644 ${DEST_FILE} 
sudo chown root:root ${DEST_FILE}
sudo restorecon -v ${DEST_FILE}
sudo systemctl daemon-reload 

echo "Success: Updated $DEST_FILE"