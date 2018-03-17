<?php



namespace GolosEventListener;


use GolosEventListener\app\AppConfig;
use GolosEventListener\app\process\MainProcess;


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define('PATH', __DIR__);
require __DIR__ . "/Autoloader.php"; // only in GrapheneNodeClient project
require __DIR__ . '/vendor/autoload.php';

echo PHP_EOL . '------ StartApp.php ------' . PHP_EOL;

$appConfig = new AppConfig();
$appConfig->addListener(['op:1:voter' => 'fafnur', 'op:0' => 'vote'], new PostIsCreatedHandler());
$appConfig->addListener(['op:0' => 'comment'], new PostIsCreatedHandler());
$appConfig->addListener(['op:0' => 'vote'], new PostIsCreatedHandler());

$mainProcess = new MainProcess(
    $appConfig,
    New RedisManager()
);
$mainProcess->start();

echo PHP_EOL . PHP_EOL;