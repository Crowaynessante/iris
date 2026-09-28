<?php
const IRIS_DB_HOST='127.0.0.1'; const IRIS_DB_PORT='3306'; const IRIS_DB_NAME='iris_db'; const IRIS_DB_USER='root'; const IRIS_DB_PASS='';

function ensure_scanner_tables(PDO $pdo): void {
    $scannerTables = [
        'records' => "CREATE TABLE IF NOT EXISTS records (
            id VARCHAR(255) PRIMARY KEY,
            fileName VARCHAR(255),
            fileType VARCHAR(50),
            fileSize INT DEFAULT 0,
            scannedAt DATETIME NULL,
            status VARCHAR(50) DEFAULT 'Pending Review',
            docType VARCHAR(100) DEFAULT 'General Institutional Data',
            rawText LONGTEXT,
            extractedData JSON,
            graphDrafts JSON,
            adminNotes TEXT,
            metadata JSON,
            updatedAt DATETIME NULL
        )",
        'saved_graphs' => "CREATE TABLE IF NOT EXISTS saved_graphs (
            id VARCHAR(255) PRIMARY KEY,
            record_id VARCHAR(255) NOT NULL,
            title VARCHAR(255),
            chart_type VARCHAR(50),
            orientation VARCHAR(20) DEFAULT 'vertical',
            value_axis_reversed BOOLEAN DEFAULT FALSE,
            value_axis_min DECIMAL(20,8) NULL,
            value_axis_max DECIMAL(20,8) NULL,
            rank_semantic BOOLEAN DEFAULT FALSE,
            rank_value_min DECIMAL(20,8) NULL,
            rank_value_max DECIMAL(20,8) NULL,
            labels JSON,
            values_data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (record_id) REFERENCES records(id) ON DELETE CASCADE
        )"
    ];

    foreach ($scannerTables as $table => $sql) {
        $q = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($q->fetch()) {
            continue;
        }
        $pdo->exec($sql);
    }
}

function db(): PDO { static $pdo; if($pdo instanceof PDO)return $pdo; $pdo=new PDO('mysql:host='.IRIS_DB_HOST.';port='.IRIS_DB_PORT.';dbname='.IRIS_DB_NAME.';charset=utf8mb4',IRIS_DB_USER,IRIS_DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_OBJ,PDO::ATTR_EMULATE_PREPARES=>false]); ensure_scanner_tables($pdo); return $pdo; }
