<?php
error_reporting(E_ERROR | E_PARSE);

require __DIR__ . '/../vendor/autoload.php';
defined('YII_DEBUG') or define('YII_DEBUG', true);
$app = new app\hejiang\Application();
$app->run();