#!/bin/bash
# host_runner.sh - ホスト側でcronジョブを実行し、DBへの記録はコンテナ内PHPへ委譲する
# Usage: host_runner.sh <job_id> <command_string>

JOB_ID="$1"
COMMAND="$2"

CONTAINER="podman_php_1"
PHP_BIN="/usr/local/bin/php"
LOG_SCRIPT="/var/www/webcron/php/log_job.php"

# 開始を記録
podman exec "$CONTAINER" "$PHP_BIN" "$LOG_SCRIPT" start "$JOB_ID"

# ホスト側でコマンドを実行
eval "$COMMAND"
EXIT_CODE=$?

# 終了を記録
podman exec "$CONTAINER" "$PHP_BIN" "$LOG_SCRIPT" end "$JOB_ID" "$EXIT_CODE"

exit $EXIT_CODE
