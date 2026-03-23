#!/bin/sh
# =============================================================================
# webcron 環境設定ファイル
# デプロイ後にこのファイルを環境に合わせて編集してください
# =============================================================================

# -----------------------------------------------------------------------------
# 実行モード設定
# コンテナ内で動作させる場合はコンテナ名を設定してください
# ホスト上で直接動作させる場合は空のままにしてください
# -----------------------------------------------------------------------------
CONTAINER="podman_php_1"

# CONTAINER を設定した場合: コンテナを起動しているOSユーザー名
# (rootless podman 等、root以外のユーザーでコンテナを管理している場合)
CONTAINER_USER="ubuntu"

# -----------------------------------------------------------------------------
# パス設定
# -----------------------------------------------------------------------------

# PHPバイナリのパス
PHP_BIN="/usr/local/bin/php"

# webcron のベースディレクトリ (ホスト上の絶対パス)
WEBCRON_BASE_DIR="/mnt/blockvolume/Projects/webcron"

# PHPスクリプトのベースパス (PHP実行環境から見たパス)
# - ホスト直接実行の場合: WEBCRON_BASE_DIR と同じ値
# - コンテナ内実行の場合: コンテナ内でのwebcronのパス
PHP_SCRIPT_BASE="/var/www/webcron"

# crontab の出力先ファイルパス (ホスト上の絶対パス)
CRONTAB_DEST="/etc/cron.d/web_cron_jobs"

# データディレクトリのホスト上のパス (host_deploy.sh・systemd から参照)
DATA_DIR="/home/ubuntu/.local/share/containers/storage/volumes/podman_webcron-data/_data"

# トリガーファイルのパス (deploy_cron.sh = PHP実行コンテキストから参照)
# コンテナモード: コンテナ内からアクセス可能なvolumeパスを指定
# 直接実行モード: この変数は使用されない (host_deploy.sh を直接呼ぶため)
TRIGGER_FILE="/var/www/webcron-data/.deploy_trigger"
