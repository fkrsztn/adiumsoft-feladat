<?php

namespace ADS\TakeHome;

include __DIR__ . "/../vendor/autoload.php";

use ADS\TakeHome\Framework\MySQL;
use ADS\TakeHome\Framework\Router;

// Debug módot csak a fejlesztői konténer kapcsol be (docker-compose.yaml,
// api-dev). Ha a változó hiányzik, a hibaválasz nem fed fel belső részleteket.
$debug = filter_var(getenv("APP_DEBUG"), FILTER_VALIDATE_BOOL);

$router = new Router(debug: $debug);

$mysql = new MySQL("db", "weather", "root", "root");

// TODO: végpontok

$router->handleRequest();
