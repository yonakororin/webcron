<?php
// php/cron_runner.php

date_default_timezone_set('Asia/Tokyo');

// 引数チェック: $argv[1] = job_id, $argv[2] = command
if ($argc < 3) {
    echo "Usage: php cron_runner.php <job_id> <command>\n";
    exit(1);
}

$job_id = (int)$argv[1];
$command = $argv[2];

// 設定読み込み (DBパス解決のため)
$config_file = __DIR__ . '/../config.json';
$config = ['db_path' => 'cron.db'];
if (file_exists($config_file)) {
    $loaded = json_decode(file_get_contents($config_file), true);
    if (is_array($loaded)) $config = array_merge($config, $loaded);
}

// DBパス解決
$db_path_setting = $config['db_path'];
if (strpos($db_path_setting, '/') === 0) {
    $db_file = $db_path_setting;
} else {
    $db_file = __DIR__ . '/../' . $db_path_setting;
}

try {
    $db = new PDO("sqlite:$db_file");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. 開始時間の記録
    $start_time = date('Y-m-d H:i:s');
    $stmt = $db->prepare("INSERT INTO job_status (job_id, start_time, end_time, exit_code) VALUES (?, ?, NULL, NULL) 
                          ON CONFLICT(job_id) DO UPDATE SET start_time=excluded.start_time, end_time=NULL, exit_code=NULL");
    $stmt->execute([$job_id, $start_time]);

    // 2. コマンド実行 (標準出力・エラー出力をリアルタイムでパススルー)
    // system() を使うことで、Cronのログメール機能などもそのまま活かせます
    $return_var = 0;
    system($command, $return_var);

    // 3. 終了時間の記録
    $end_time = date('Y-m-d H:i:s');
    $stmt = $db->prepare("UPDATE job_status SET end_time = ?, exit_code = ? WHERE job_id = ?");
    $stmt->execute([$end_time, $return_var, $job_id]);

    exit($return_var);

} catch (Exception $e) {
    fwrite(STDERR, "Runner Error: " . $e->getMessage() . "\n");
    exit(1);
}
?>