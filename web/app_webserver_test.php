<?php

$uri = $_SERVER['REQUEST_URI'];
if ($uri !== '/' && file_exists(__DIR__ . '/' . $uri)) {
    return false; // serve the requested resource as-is.
}
require 'app_test.php';
