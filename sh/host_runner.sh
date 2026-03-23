#!/bin/bash
# host_runner.sh - ホスト側でcronジョブを実行し、DBへの記録をPHP経由で行う
# Usage: host_runner.sh <job_id> <command_string>

JOB_ID="$1"
COMMAND="$2"

# 設定読み込み
SCRIPT_DIR="$(dirname "$(readlink -f "$0")")"
. "$SCRIPT_DIR/../conf/env.sh"

LOG_SCRIPT="$PHP_SCRIPT_BASE/php/log_job.php"

# PHP実行ヘルパー (コンテナ有無を吸収)
_run_php() {
    if [ -n "$CONTAINER" ]; then
        sudo -u "$CONTAINER_USER" podman exec "$CONTAINER" "$PHP_BIN" "$@"
    else
        "$PHP_BIN" "$@"
    fi
}

# 開始を記録
_run_php "$LOG_SCRIPT" start "$JOB_ID"

# ホスト側でコマンドを実行
eval "$COMMAND"
EXIT_CODE=$?

# 終了を記録
_run_php "$LOG_SCRIPT" end "$JOB_ID" "$EXIT_CODE"

exit $EXIT_CODE
