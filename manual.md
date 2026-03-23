# WebCron 使い方ガイド

## 概要

WebCron は、サーバーの crontab をブラウザ上で管理するツールです。
ジョブの追加・編集・削除を行うと、自動的に crontab ファイルへ反映されます。

画面上部のタブで機能を切り替えます。

| タブ | 内容 |
|---|---|
| ジョブ管理 | ジョブの追加・編集・削除、実行状況の確認 |
| 環境設定 | cron 実行ユーザー・環境変数・ラッパースクリプトの管理 |
| Crontabファイル | 実際に crond へ反映されている crontab ファイルの内容を表示 |
| 使い方 | このドキュメント |

---

## デプロイ設定

WebCron はコンテナ環境・ホスト直接実行のどちらにも対応しています。
デプロイ後に編集が必要なファイルは **2つ** だけです。

### 1. `conf/env.sh` — シェルスクリプト用の環境設定

```sh
# コンテナ名 (コンテナ内で動作させる場合に設定。直接実行の場合は空)
CONTAINER="podman_php_1"

# コンテナを起動しているOSユーザー (rootless podman の場合)
CONTAINER_USER="ubuntu"

# PHPバイナリのパス
PHP_BIN="/usr/local/bin/php"

# webcron のベースディレクトリ (ホスト上の絶対パス)
WEBCRON_BASE_DIR="/opt/webcron"

# PHPスクリプトのベースパス (PHP実行環境から見たパス)
# ホスト直接実行の場合: WEBCRON_BASE_DIR と同じ
# コンテナ内実行の場合: コンテナ内でのwebcronのパス (例: /var/www/webcron)
PHP_SCRIPT_BASE="/var/www/webcron"

# crontab の出力先ファイルパス (ホスト上の絶対パス)
CRONTAB_DEST="/etc/cron.d/web_cron_jobs"

# データディレクトリのホスト上のパス (DB・トリガーファイル等)
# コンテナモードの場合: volume のホスト側パス
# 直接実行モードの場合: config.json の db_path と同じディレクトリ
DATA_DIR="/var/lib/webcron"
```

**モード別の設定早見表:**

| 項目 | コンテナ動作 | ホスト直接動作 |
|---|---|---|
| `CONTAINER` | コンテナ名 (例: `podman_php_1`) | `""` (空) |
| `CONTAINER_USER` | podman を起動しているユーザー | 不使用 |
| `PHP_BIN` | コンテナ内の PHP パス | ホストの PHP パス (例: `php`) |
| `PHP_SCRIPT_BASE` | コンテナ内の webcron パス | `WEBCRON_BASE_DIR` と同じ値 |
| `DATA_DIR` | volume のホスト側パス | DB ファイルと同じディレクトリ |

> `CONTAINER` を空にするだけでホスト直接実行モードになります。
> `deploy_cron.sh` が `host_deploy.sh` を直接呼び出すよう自動切り替えされます。

### 2. `config.json` — PHP 用の設定

```json
{
  "db_path": "/var/www/webcron-data/cron.db",
  "updater_path": "./sh/deploy_cron.sh",
  "back_url": "../../",
  "host_runner": "/opt/webcron/sh/host_runner.sh",
  "crontab_dest": "/etc/cron.d/web_cron_jobs"
}
```

| キー | 説明 |
|---|---|
| `db_path` | SQLite DB ファイルのパス (PHP から見たパス) |
| `updater_path` | デプロイスクリプトのパス (`./sh/deploy_cron.sh` から変更不要) |
| `back_url` | ヘッダーの戻るリンク先 |
| `host_runner` | `host_runner.sh` のホスト上の絶対パス |
| `crontab_dest` | crontab 出力先ファイルのパス (Crontabファイルタブで表示) |

### コンテナモードの追加セットアップ

コンテナモードでは、Web UI の操作をトリガーとしてホスト側でデプロイを実行するため、systemd の設定が必要です。

```bash
# systemd ユニットをインストール
sudo cp systemd/webcron-deploy.path /etc/systemd/system/
sudo cp systemd/webcron-deploy.service /etc/systemd/system/

# サービスを有効化・起動
sudo systemctl daemon-reload
sudo systemctl enable --now webcron-deploy.path
```

また、PHP コンテナが `/etc/cron.d` をホストからマウントできるよう `compose.yml` に追記してください。

```yaml
services:
  php:
    volumes:
      - /etc/cron.d:/etc/cron.d:ro   # 追加
```

---

## ジョブ管理

### ジョブの追加

1. 上部タブの **ジョブ管理** を開く
2. フォームに以下を入力して **ジョブを追加** ボタンをクリック

| 項目 | 説明 | 例 |
|------|------|----|
| スケジュール | cron 書式（分 時 日 月 曜） | `0 3 * * *` |
| 実行コマンド | 実行するコマンドのフルパス | `/usr/bin/php /var/www/script.php` |
| 説明 | 任意のメモ | 夜間バッチ |

### ジョブの編集・削除

- ジョブ一覧の **✏️ ID** リンクをクリックすると編集フォームが開きます
- **ジョブを更新** で保存、**削除** で削除します
- 保存・削除のどちらも実行後、crontab へ自動反映されます

### ジョブの無効化（コメントアウト）

コマンドの先頭に `#` を付けると、crontab 上でコメント扱いとなり実行されません。
削除せずに一時停止したい場合に使用します。

```
# /usr/bin/php /var/www/script.php
```

### ジョブ一覧の検索

一覧上部の検索ボックスでリアルタイムに絞り込みができます。

| 記法 | 動作 | 例 |
|------|------|----|
| スペース区切り | AND 検索 | `php batch` |
| `!キーワード` | 除外検索 | `!エラー` |
| `"スペースを含む語"` | フレーズ検索 | `"Err: 0"` |

### コマンド列の変数ハイライト

ジョブ一覧のコマンド列で、`${変数名}` 形式の参照箇所が色付きで表示されます。

| 色 | 種別 |
|---|---|
| 水色 (bold) | 環境変数（環境設定タブで登録した変数） |
| 赤 (bold) | ラッパースクリプト定義（環境設定タブで登録したラッパー） |

### 実行状況の確認

- ジョブ一覧の **実行状況確認** 列に、直近の開始時刻・終了時刻・終了コード（Err）が表示されます
- **自動更新(1分)** チェックボックスをオンにすると、1分ごとに状態が自動更新されます

---

## 環境設定

### Cron 実行ユーザー

crontab ファイルに記述される実行ユーザー名を指定します。
デフォルトは `root` です。変更後は **設定を保存** をクリックしてください。
次回のジョブ追加・更新・削除のタイミングで crontab へ反映されます。

### 環境変数

crontab ファイルの先頭に書き込まれる変数を管理します。
`PATH` や独自の設定値を定義することで、ジョブ実行時の環境を制御できます。

- **登録/更新**: 変数名・値・説明を入力して **登録/更新** ボタンをクリック
- **編集**: 一覧の **編集** ボタンをクリックするとフォームに値がセットされます
- **削除**: 一覧の **削除** ボタンをクリック

> 登録した環境変数は、crontab 上で以下のように出力されます。
> `VARNAME="value"`

### ラッパースクリプト定義

ジョブコマンド内で `${変数名}` と記述すると、crontab 生成時にここで定義した値に置換されます。
長いコマンドや共通前処理をまとめたい場合に便利です。

**使用例:**

1. ラッパーとして `SETUP` を登録し、値に `export APP_ENV=production;` を設定
2. ジョブコマンドを `${SETUP} /usr/bin/php /var/www/script.php` と記述
3. 生成される crontab のコマンドは `export APP_ENV=production; /usr/bin/php /var/www/script.php` になります

---

## Crontabファイル

上部タブの **Crontabファイル** を開くと、crond に実際に反映されている crontab ファイルの内容を確認できます。

- ファイルパスと最終更新日時が表示されます
- **更新** ボタンをクリックすると最新の内容を再取得します
- 表示対象のファイルパスは `config.json` の `crontab_dest` で設定します

---

## アラートスクリプト

異常終了時に通知を送るラッパースクリプトを `sh/` 以下に用意しています。
ジョブのコマンドをこれらのスクリプトで包むことで、失敗時に自動アラートを送れます。

| ファイル | 送信先 |
|---|---|
| `sh/alert_discord.sh` | Discord (Embed形式) |
| `sh/alert_googlechat.sh` | Google Chat |
| `sh/alert_slack.sh` | Slack |
| `sh/alert_mattermost.sh` | Mattermost |

### 共通の使い方

全スクリプトで `WEBHOOK_URL` 環境変数を使います。

```bash
export WEBHOOK_URL=https://...your-webhook-url...
bash /path/to/alert_discord.sh <実行コマンド> [引数...]
```

ジョブコマンドへの組み込み例:

```
export WEBHOOK_URL=https://hooks.slack.com/services/xxx; bash /opt/webcron/sh/alert_slack.sh /usr/bin/python3 /opt/scripts/batch.py
```

### オプション: 環境名の表示

`RESERVED_ALERT_ENV` を設定すると、アラートメッセージに環境名が表示されます。

```bash
export RESERVED_ALERT_ENV=production
```

### 各サービスのペイロード形式

| サービス | 形式 | 備考 |
|---|---|---|
| Discord | `embeds` (Embed) | フィールドで構造化、赤色 |
| Google Chat | `text` (プレーンテキスト) | Markdown記法 |
| Slack | `attachments` + `fields` | Incoming Webhook |
| Mattermost | `attachments` + `fields` | Slack互換、`username`/`icon_emoji` 付き |

---

## 内部動作

### crontab 自動反映の仕組み

ジョブや環境変数を変更するたびに以下の処理が自動実行されます。

**ホスト直接実行モード:**
```
sh/deploy_cron.sh
  └─ sh/host_deploy.sh
       └─ php/crontab_generator.php  (DBからcrontab内容を生成)
            └─ CRONTAB_DEST  (conf/env.sh で指定したパスへ出力)
```

**コンテナモード:**
```
sh/deploy_cron.sh  (コンテナ内)
  └─ .deploy_trigger ファイルを作成
       ↓ systemd webcron-deploy.path が即時検知
sh/host_deploy.sh  (ホスト側)
  └─ podman exec php/crontab_generator.php
       └─ CRONTAB_DEST へ出力 + crond に HUP シグナル
```

### crontab 生成ルール

生成されるファイルの構造は以下のとおりです。

```
# Generated by Web Cron Manager
SHELL=/bin/bash
PATH=/sbin:/bin:/usr/sbin:/usr/bin
VARNAME="value"   ← 環境変数

* * * * * ubuntu bash '/opt/webcron/sh/host_runner.sh' <id> '<command>'
```

- 通常のジョブはホスト側の `host_runner.sh` 経由で実行されます
- `#` で始まるコマンドはランナーを通さずそのまま出力されます

### ジョブ実行フロー

**ホスト直接実行モード:**
```
cron
 └─ bash host_runner.sh <job_id> <command>
      ├─ php log_job.php start   (開始時刻をDBに記録)
      ├─ eval <command>          (コマンド本体を実行)
      └─ php log_job.php end     (終了時刻・終了コードをDBに記録)
```

**コンテナモード:**
```
cron
 └─ bash host_runner.sh <job_id> <command>
      ├─ podman exec php log_job.php start   (開始時刻をDBに記録)
      ├─ eval <command>                      (コマンド本体をホストで実行)
      └─ podman exec php log_job.php end     (終了時刻・終了コードをDBに記録)
```

### データ保存先

全データは SQLite データベース（`cron.db`）に保存されます。
パスは `config.json` の `db_path` で変更可能です。
