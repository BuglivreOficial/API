<?php

require "../vendor/autoload.php";

try {
    (new Core\App())->init();
} catch (\Core\Router\Exception\RoutingException $e) {
    echo $e->getMessage();
}