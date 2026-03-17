<?php
// includes/db.php

try {
    $db = new PDO("sqlite:$db_file");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("CREATE TABLE IF NOT EXISTS jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        command TEXT NOT NULL,
        schedule TEXT NOT NULL,
        description TEXT
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS environment_variables (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        value TEXT,
        description TEXT
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        name TEXT PRIMARY KEY,
        value TEXT
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS wrappers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        value TEXT,
        description TEXT
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS job_status (
        job_id INTEGER PRIMARY KEY,
        start_time DATETIME,
        end_time DATETIME,
        exit_code INTEGER,
        FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE CASCADE
    )");
    
} catch (PDOException $e) {
    echo "<div style='color:red; font-weight:bold;'>データベース接続エラー: " . htmlspecialchars($e->getMessage()) . "<br>DB Path: " . htmlspecialchars($db_file) . "</div>";
    $db = null;
    return;
}