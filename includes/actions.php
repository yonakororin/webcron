<?php
$message = '';
$error = '';

// 実行ユーザー設定の更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $cron_user = trim($_POST['cron_user']);
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $cron_user)) {
        $error = "ユーザー名に不正な文字が含まれています。";
    } else {
        try {
            $stmt = $db->prepare("REPLACE INTO settings (name, value) VALUES ('cron_user', ?)");
            $stmt->execute([$cron_user]);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=env_settings&msg=settings_updated');
            exit;
        } catch (PDOException $e) {
            $error = "設定の保存に失敗しました: " . $e->getMessage();
        }
    }
}

// Crontab反映処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_crontab'])) {
    // $updater_script は index.php で定義
    if (file_exists($updater_script)) {
        $output = [];
        $return_var = 0;
        $cmd = 'sudo ' . $updater_script . ' 2>&1';
        exec($cmd, $output, $return_var);
        
        if ($return_var === 0) {
            $output_str = implode("\n", $output);
            $msg_text = empty($output_str) ? "設定を反映しました。" : $output_str;
            header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=applied&detail=' . urlencode($msg_text));
            exit;
        } else {
            $error = "更新失敗 (Code: $return_var):<br>" . nl2br(htmlspecialchars(implode("\n", $output)));
        }
    } else {
        $error = "更新スクリプトが見つかりません: " . htmlspecialchars($updater_script);
    }
}

// C. ジョブの削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && isset($_POST['id'])) {
    $job_id = (int)$_POST['id'];
    try {
        $stmt = $db->prepare("DELETE FROM jobs WHERE id = ?");
        $stmt->execute([$job_id]);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=deleted');
        exit;
    } catch (PDOException $e) { $error = "削除失敗: " . $e->getMessage(); }
}

// B. ジョブの修正処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update']) && !isset($_POST['delete'])) {
    $id = (int)$_POST['id'];
    $command = trim($_POST['command']);
    $schedule = trim($_POST['schedule']);
    $description = trim($_POST['description']);
    $description = filter_var($description, FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($command) || empty($schedule)) { $error = "必須項目不足"; } else {
        try {
            $stmt = $db->prepare("UPDATE jobs SET command = ?, schedule = ?, description = ? WHERE id = ?");
            $stmt->execute([$command, $schedule, $description, $id]);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=updated');
            exit; 
        } catch (PDOException $e) { $error = "更新失敗: " . $e->getMessage(); }
    }
}

// A. ジョブの追加処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $command = trim($_POST['command']);
    $schedule = trim($_POST['schedule']);
    $description = trim($_POST['description']);
    $description = filter_var($description, FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($command) || empty($schedule)) { $error = "必須項目不足"; } else {
        try {
            $stmt = $db->prepare("INSERT INTO jobs (command, schedule, description) VALUES (?, ?, ?)");
            $stmt->execute([$command, $schedule, $description]);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=added');
            exit; 
        } catch (PDOException $e) { $error = "追加失敗: " . $e->getMessage(); }
    }
}

// G. 環境変数の追加/更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['env_add'])) {
    $name = trim($_POST['env_name']);
    $value = trim($_POST['env_value']);
    $description = trim($_POST['env_description']);
    $name = filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS);
    $value = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
    $description = filter_var($description, FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($name)) { $error = "変数名必須"; } else {
        try {
            $stmt = $db->prepare("INSERT INTO environment_variables (name, value, description) VALUES (?, ?, ?) ON CONFLICT(name) DO UPDATE SET value=excluded.value, description=excluded.description");
            $stmt->execute([$name, $value, $description]);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=env_settings&msg=env_added'); 
            exit; 
        } catch (PDOException $e) { $error = "環境変数登録失敗: " . $e->getMessage(); }
    }
}

// H. 環境変数の削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['env_delete']) && isset($_POST['env_id'])) {
    $env_id = (int)$_POST['env_id'];
    try {
        $stmt = $db->prepare("DELETE FROM environment_variables WHERE id = ?");
        $stmt->execute([$env_id]);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=env_settings&msg=env_deleted'); 
        exit;
    } catch (PDOException $e) { $error = "環境変数削除失敗: " . $e->getMessage(); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wrapper_add'])) {
    $name = trim($_POST['wrapper_name']);
    $value = trim($_POST['wrapper_value']);
    $description = trim($_POST['wrapper_description']);
    
    // 入力値のサニタイズ
    $name = filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS);
    // valueはコマンドを含むため過度なサニタイズは避けるが、HTMLタグ等は無効化
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); 
    $description = filter_var($description, FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($name)) {
        $error = "ラッパー名は必須です。";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO wrappers (name, value, description) VALUES (?, ?, ?) 
                                  ON CONFLICT(name) DO UPDATE SET value=excluded.value, description=excluded.description");
            $stmt->execute([$name, $value, $description]);
            
            header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=env_settings&msg=wrapper_added'); 
            exit; 
        } catch (PDOException $e) {
            $error = "ラッパー登録失敗: " . $e->getMessage();
        }
    }
}

// ★ 新規: ラッパーの削除処理 ★
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wrapper_delete']) && isset($_POST['wrapper_id'])) {
    $wrapper_id = (int)$_POST['wrapper_id'];
    try {
        $stmt = $db->prepare("DELETE FROM wrappers WHERE id = ?");
        $stmt->execute([$wrapper_id]);
        
        header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=env_settings&msg=wrapper_deleted'); 
        exit;
    } catch (PDOException $e) {
        $error = "ラッパー削除失敗: " . $e->getMessage();
    }
}