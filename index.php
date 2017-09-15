<?php

sleep(2); # wait for database to start

$config = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'database' => getenv('DB_DATABASE') ?: 'test',
];

function new_pdo_connection(bool $emulatePrepares = true): PDO
{
    global $config;

    $pdo = new PDO(
        "mysql:host={$config['host']}",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => $emulatePrepares,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['database']}");
    $pdo->exec("USE {$config['database']}");

    $pdo->exec(<<<'SQL'
    CREATE TABLE IF NOT EXISTS settings  (
      name varchar(255) NOT NULL,
      value varchar(255),
      created_at timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
      updated_at timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
      PRIMARY KEY (name)
    )
SQL
        );

    return $pdo;
}

// upsert a value and let mysql set created_at and updated_at timestamps
$pdo = new_pdo_connection();
$pdo->prepare('REPLACE INTO settings (name, value) VALUES (?, ?)')->execute(['php_version', PHP_VERSION]);
unset($pdo);

// select with emulated prepares
$pdo = new_pdo_connection();
$stmt = $pdo->prepare('SELECT * FROM settings WHERE name = "php_version" LIMIT 1');
$stmt->execute();
$result = $stmt->fetch();
echo "Emulated prepares 1:\n";
echo print_r($result, true);
unset($pdo);
assert(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/', $result['updated_at']));

echo "\n";

// select without emulated prepares
$pdo = new_pdo_connection(false);
$stmt = $pdo->prepare('SELECT * FROM settings WHERE name = "php_version" LIMIT 1');
$stmt->execute();
$result = $stmt->fetch();
echo "Emulated prepares 0:\n";
echo print_r($result, true);
unset($pdo);
assert(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/', $result['updated_at'])) or die("Fractional seconds missing\n");

echo "No errors\n";
