<?php

namespace ADS\TakeHome;

include __DIR__ . "/../vendor/autoload.php";

use ADS\TakeHome\Framework\MySQL;

// Debug módot csak a fejlesztői konténer kapcsol be (docker-compose.yaml,
// api-dev). Ha a változó hiányzik, a hibaválasz nem fed fel belső részleteket.
$debug = filter_var(getenv("APP_DEBUG"), FILTER_VALIDATE_BOOL);

$mysql = new MySQL("db", "weather", "root", "root");

Api::createRouter($mysql, debug: $debug)->handleRequest();
