g!/bin/bash

# catch_alert: コマンドを実行し、失敗時にGoogle Chatへアラートを送信するラッパースクリプト
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

    # User-provided hardcoded URL (commented out generic one to match user's state)
    
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
    # cmd_name has the args info roughly, but let's try to parse if needed. 
    # Actually cmd_name passed from main is "FULL_CMD".
    local args_list="$cmd_name"

    # メッセージの作成
    # Google Chat向けのフォーマット
    local lf=$'\n'
    local trace_output=""
    trace_output+="🚨 スクリプト${script_name}の異常終了を検知しました🚨 ${lf}"
    trace_output+="・ホスト: ${target_host} ${lf}"
    trace_output+="・環境名: ${envname} ${lf}"
    trace_output+="・ディレクトリ: ${current_dir} ${lf}"
    trace_output+="・コマンドライン: ${args_list} ${lf}"
    trace_output+="・発生日時: "$(date '+%Y-%m-%d %H:%M:%S')" ${lf}"
    trace_output+="・終了ステータス: \`${exit_code}\` ${lf}"
    trace_output+="${lf}*スタックトレース (標準エラー出力):*${lf}"
    trace_output+="\`\`\`${lf}"
    trace_output+="${stderr_output}${lf}"
    trace_output+="\`\`\`"

    # curlを使用して送信
    # jqを使てコンパクトなJSON出力 (-c) を生成
    local payload=$(jq -nc --arg text "$trace_output" '{text: $text}')
    
    curl -s -H "Content-Type: application/json" -d "$payload" "$WEBHOOK_URL" > /dev/null
    
    if [ $? -eq 0 ]; then
        echo "Google Chatへアラートを送信しました。" >&2
    else
        echo "Google Chatへのアラート送信に失敗しました。" >&2
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
