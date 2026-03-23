<?php
// log_job.php - ジョブの開始・終了をDBに記録する
// Usage: php log_job.php start <job_id>
//        php log_job.php end <job_id> <exit_code>

date_default_timezone_set('Asia/Tokyo');

if ($argc < 3) {
    fwrite(STDERR, "Usage: php log_job.php <start|end> <job_id> [exit_code]\n");
    exit(1);
}

$mode   = $argv[1];
$job_id = (int)$argv[2];

$config_file = __DIR__ . '/../config.json';
$config = ['db_path' => 'cron.db'];
if (file_exists($config_file)) {
    $loaded = json_decode(file_get_contents($config_file), true);
    if (is_array($loaded)) $config = array_merge($config, $loaded);
}

$db_path_setting = $config['db_path'];
$db_file = ($db_path_setting[0] === '/') ? $db_path_setting : __DIR__ . '/../' . $db_path_setting;

try {
    $db = new PDO("sqlite:$db_file");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($mode === 'start') {
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO job_status (job_id, start_time, end_time, exit_code) VALUES (?, ?, NULL, NULL)
                              ON CONFLICT(job_id) DO UPDATE SET start_time=excluded.start_time, end_time=NULL, exit_code=NULL");
        $stmt->execute([$job_id, $now]);
    } elseif ($mode === 'end') {
        $exit_code = isset($argv[3]) ? (int)$argv[3] : 0;
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("UPDATE job_status SET end_time = ?, exit_code = ? WHERE job_id = ?");
        $stmt->execute([$now, $exit_code, $job_id]);
    } else {
        fwrite(STDERR, "Unknown mode: $mode\n");
        exit(1);
    }
} catch (Exception $e) {
    fwrite(STDERR, "log_job Error: " . $e->getMessage() . "\n");
    exit(1);
}
