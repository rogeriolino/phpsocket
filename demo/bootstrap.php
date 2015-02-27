<?php


$load = function($className) {
    require_once dirname(__DIR__) . '/src/' . str_replace("\\", DIRECTORY_SEPARATOR, $className) . ".php";
};

spl_autoload_register($load);

error_reporting(E_ALL);