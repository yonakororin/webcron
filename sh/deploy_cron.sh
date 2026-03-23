#!/bin/sh
# deploy_cron.sh - crontab デプロイのトリガー
# webアプリ (PHP) から呼ばれる
#
# コンテナモード:
#   トリガーファイルを作成し、ホスト側の systemd webcron-deploy.path が
#   host_deploy.sh を起動するのを待つ
#
# 直接実行モード (CONTAINER が空):
#   host_deploy.sh を直接実行する

set -eu

SCRIPT_DIR="$(dirname "$(readlink -f "$0")")"
. "$SCRIPT_DIR/../conf/env.sh"

if [ -n "$CONTAINER" ]; then
    # コンテナモード: トリガーファイルを作成して systemd に任せる
    touch "$DATA_DIR/.deploy_trigger"
    echo "Deploy trigger created."
else
    # 直接実行モード: 同期的にデプロイ
    exec "$SCRIPT_DIR/host_deploy.sh"
fi
