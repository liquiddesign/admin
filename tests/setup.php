<?php

require_once __DIR__ . '/../vendor/autoload.php';

$configFile = __DIR__ . '/_configs/config.neon';
$sourceFile = __DIR__ . '/_sql/_test_db.sql';
$config = \Nette\Neon\Neon::decode(\file_get_contents($configFile))['storm']['connections']['default'];

// create test DB and fill with test data
$pdo = new \PDO("$config[driver]:host=$config[host]", $config['user'], $config['password']);
$pdo->query("CREATE DATABASE IF NOT EXISTS $config[dbname] CHARACTER SET $config[charset] COLLATE $config[collate]");
$pdo->query("USE $config[dbname]");
$pdo->query(\file_get_contents($sourceFile));
