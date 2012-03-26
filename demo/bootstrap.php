<?php
define('BASE', dirname(dirname(__FILE__)));

$load = function($className) {
    $filename = BASE . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $className) . ".php";
    if (file_exists($filename)) {
        require_once($filename);
    }
};

spl_autoload_register($load);

error_reporting(E_ALL);