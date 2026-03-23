#!/bin/sh
# deploy_cron.sh - ホスト側デプロイのトリガーファイルを作成する
# コンテナ内から呼ばれる。実際のデプロイはホスト側の host_deploy.sh が行う。
# (systemd webcron-deploy.path がトリガーファイルを検知し webcron-deploy.service を起動)

set -eu

TRIGGER_FILE="/var/www/webcron-data/.deploy_trigger"

touch "$TRIGGER_FILE"

echo "Deploy trigger created."
