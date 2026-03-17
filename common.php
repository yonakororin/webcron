<?php
// extends/webcron/common.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_current_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    $path = str_replace('\\', '/', $path);
    $path = rtrim($path, '/');
    return $protocol . $host . $path;
}

/*
if (!isset($_SESSION['user'])) {
    if (!headers_sent()) {
        header("Location: $sso_url/?redirect_uri=" . urlencode("$base_url/callback.php"));
        exit;
    } else {
        echo "<script>window.location.href = '" . $sso_url . "/?redirect_uri=" . urlencode("$base_url/callback.php") . "';</script>";
        echo "<p>認証が必要です。ログイン画面へ移動します...</p>";
        $jobs = []; $env_vars = []; $wrappers = []; $message = ''; $error = ''; $job_to_edit = null; $env_names = []; $wrapper_names = [];
        return;
    }
}
*/

// エラー報告設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Tokyo');

// 1. 設定読み込み
$config_file = __DIR__ . '/config.json';
$config = [
    'db_path' => 'cron.db',
    'updater_path' => './sh/deploy_cron.sh',
    'back_url' => '../../'
];
if (file_exists($config_file)) {
    $loaded_config = json_decode(file_get_contents($config_file), true);
    if (is_array($loaded_config)) $config = array_merge($config, $loaded_config);
}

$db_file = __DIR__ . '/' . $config['db_path'];
$updater_script = __DIR__ . '/' . $config['updater_path'];
$back_url = $config['back_url'];

// 2. DB接続 & 3. アクション処理
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/actions.php';

// ★ AJAXによるステータス取得用API ★
if (isset($_GET['ajax_status'])) {
    header('Content-Type: application/json');
    try {
        $sql = "SELECT j.id, s.start_time, s.end_time, s.exit_code FROM jobs j LEFT JOIN job_status s ON j.id = s.job_id";
        $statuses = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $response = [];
        foreach ($statuses as $st) {
             $html = '';
            if (empty($st['start_time'])) {
                $html = '<span style="color: #64748b;">未実行</span>';
            } else {
                $start = date('m/d H:i:s', strtotime($st['start_time']));
                $html .= '<div style="display: inline;"><span style="color: #38bdf8; font-weight: bold;">開始：</span>' . $start . '</div>';
                
                if (!empty($st['end_time'])) {
                    $end = date('m/d H:i:s', strtotime($st['end_time']));
                    $html .= '<div style="display: inline;"><span style="color:#d8b4fe; font-weight: bold;">終了：</span>' . $end . '</div>';
                    
                    if ($st['exit_code'] !== null) {
                        if($st['exit_code'] != 0 ) {
                            $html .= '<div style="display: inline; color: #f87171; font-weight: bold;">Err：' . intval($st['exit_code']) . '</div>';
                        }
                        else {
                            $html .= '<div style="display: inline; color:#4ade80; font-weight: bold;">Err：' . intval($st['exit_code']) . '</div>';
                        }
                    }
                } else {
                    $html .= '<div style="color: #fb923c; font-weight: bold;">実行中...</div>';
                }
            }
            $response[$st['id']] = $html;
        }
        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// 4. データ取得
$job_to_edit = null; 
$jobs = [];
$env_vars = []; 
$wrappers = [];
$cron_user_setting = 'root';

$env_names = [];
$wrapper_names = [];

// 編集ID取得
if (isset($_GET['edit_id']) && $db) {
    $edit_id = (int)$_GET['edit_id'];
    try {
        $stmt = $db->prepare("SELECT * FROM jobs WHERE id = ?");
        $stmt->execute([$edit_id]);
        $job_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $error = "取得失敗"; }
}

// データ一覧取得
if ($db) {
    try {
        $sql = "SELECT j.*, s.start_time, s.end_time, s.exit_code FROM jobs j LEFT JOIN job_status s ON j.id = s.job_id ORDER BY j.id DESC";
        $jobs = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $env_vars = $db->query("SELECT * FROM environment_variables ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $wrappers = $db->query("SELECT * FROM wrappers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $db->query("SELECT value FROM settings WHERE name = 'cron_user'");
        $setting_val = $stmt->fetchColumn();
        if ($setting_val) $cron_user_setting = $setting_val;
        
        $env_names = array_column($env_vars, 'name');
        $wrapper_names = array_column($wrappers, 'name');
    } catch (PDOException $e) {
        if (!isset($error)) $error = "";
        $error .= "データ取得エラー: " . $e->getMessage();
    }
}

if (!function_exists('highlightCommand')) {
    function highlightCommand($command, $env_names, $wrapper_names) {
        $safe_command = htmlspecialchars($command, ENT_QUOTES, 'UTF-8');
        foreach ($wrapper_names as $name) {
            $target = '${' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '}';
            $replacement = '<span class="var-wrapper">' . $target . '</span>';
            $safe_command = str_replace($target, $replacement, $safe_command);
        }
        foreach ($env_names as $name) {
            $target = '${' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '}';
            $replacement = '<span class="var-env">' . $target . '</span>';
            $safe_command = str_replace($target, $replacement, $safe_command);
        }
        return $safe_command;
    }
}

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'settings_updated') $message = "実行ユーザー設定を更新しました。";
    elseif ($_GET['msg'] === 'wrapper_added') $message = "ラッパー定義を登録/更新しました。";
    elseif ($_GET['msg'] === 'wrapper_deleted') $message = "ラッパー定義を削除しました。";
    elseif ($_GET['msg'] === 'applied') {
        $detail = isset($_GET['detail']) ? htmlspecialchars($_GET['detail']) : '';
        $message = "設定をCrontabに反映しました。" . ($detail ? "<br><small>$detail</small>" : "");
    }
    else $message = "処理が完了しました。";
}
