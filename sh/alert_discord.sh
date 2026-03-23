#!/bin/bash

# catch_alert: コマンドを実行し、失敗時にDiscordへアラートを送信するラッパースクリプト
# 使用法: ./catch_alert <コマンド> [引数...]
#

# WEBHOOK_URLは外からexport WEBHOOK_URL=xxxxxxのように渡す
#WEBHOOK_URL=

# アラート送信関数
send_alert() {
    local cmd_name="$1" # 失敗したコマンド
    local exit_code="$2" # 終了コード
    local stderr_output="$3" # 標準エラー出力

    # 依存関係のチェック
    if ! command -v jq &> /dev/null; then
        echo "エラー: jqがインストールされていません。アラートを送信できません。" >&2
        return 1
    fi

    if [ -z "$WEBHOOK_URL" ]; then
        echo "エラー: Webhook URLが設定ファイルに見つかりません。" >&2
        return 1
    fi

    local script_name=$(basename "$0")
    local target_host="${HOSTNAME:-$(uname -n)}"
    local envname=""
    if [[ -v RESERVED_ALERT_ENV ]]; then
        envname=${RESERVED_ALERT_ENV}
    fi
    local current_dir=$(pwd)
    local args_list="$cmd_name"

    # Discord embed の description (stderrをコードブロックで囲む)
    local lf=$'\n'
    local description=""
    description+="**スタックトレース (標準エラー出力):**${lf}"
    description+="\`\`\`${lf}"
    description+="${stderr_output}${lf}"
    description+="\`\`\`"

    # Discord向けのembedペイロード作成
    # color: 赤 (16711680 = 0xFF0000)
    local payload
    payload=$(jq -nc \
        --arg title "🚨 スクリプト${script_name}の異常終了を検知しました" \
        --arg host "${target_host}" \
        --arg envname "${envname}" \
        --arg dir "${current_dir}" \
        --arg cmd "${args_list}" \
        --arg ts "$(date '+%Y-%m-%d %H:%M:%S')" \
        --arg code "${exit_code}" \
        --arg desc "${description}" \
        '{
            embeds: [{
                title: $title,
                color: 16711680,
                fields: [
                    {name: "ホスト",           value: $host,    inline: true},
                    {name: "環境名",           value: (if $envname == "" then "(未設定)" else $envname end), inline: true},
                    {name: "終了ステータス",   value: ("`" + $code + "`"), inline: true},
                    {name: "ディレクトリ",     value: $dir,     inline: false},
                    {name: "コマンドライン",   value: $cmd,     inline: false},
                    {name: "発生日時",         value: $ts,      inline: false}
                ],
                description: $desc
            }]
        }')

    # curlを使用して送信
    curl -s -H "Content-Type: application/json" -d "$payload" "$WEBHOOK_URL" > /dev/null

    if [ $? -eq 0 ]; then
        echo "Discordへアラートを送信しました。" >&2
    else
        echo "Discordへのアラート送信に失敗しました。" >&2
    fi
}

# メイン処理

if [ $# -eq 0 ]; then
    echo "使用法: $0 <コマンド> [引数...]" >&2
    exit 1
fi

# スタックトレース収集用スクリプトの作成
TRACE_HELPER=$(mktemp)
cat << 'EOF' > "$TRACE_HELPER"
# エラートラップの設定
set -E
error_handler() {
    local exit_code=$?
    # 重複出力を防ぐための簡易チェック（完全ではないが実用的）
    if [ -z "${__ALREADY_TRACED:-}" ]; then
        __ALREADY_TRACED=1
        printf '%s\n' "" "--- スタックトレース ---" >&2
        local i=0
        while caller $i >&2; do
            ((i++))
        done
        printf '%s\n' "----------------------" >&2
    fi
}
trap error_handler ERR
EOF

# 標準エラー出力を一時ファイルにキャプチャ
TMP_STDERR=$(mktemp)

# コマンドの実行
# 引数をそのまま渡すために "$@" を使用
# BASH_ENVを設定して、bashスクリプト実行時にトラップを有効化
# 標準エラー出力を一時ファイルに保存しつつ、コンソールにも出力 (tee)
BASH_ENV="$TRACE_HELPER" "$@" 2> >(tee "$TMP_STDERR" >&2)

EXIT_CODE=$?

# クリーンアップ（ヘルパースクリプト）
rm -f "$TRACE_HELPER"

# 失敗時のチェック
if [ $EXIT_CODE -ne 0 ]; then
    # キャプチャした標準エラー出力を読み込み
    STDERR_CONTENT=$(cat "$TMP_STDERR")

    # アラート送信
    # コンテキストとして実行されたコマンド全体を渡す
    FULL_CMD="$*"
    send_alert "$FULL_CMD" "$EXIT_CODE" "$STDERR_CONTENT"
fi

# クリーンアップ
rm -f "$TMP_STDERR"

exit $EXIT_CODE
