<?php
mb_internal_encoding('utf-8');
error_reporting(1);

define('APP_DIR', "./protected/");
/*
ini_set("log_errors", 1);
ini_set("error_log", APP_DIR."php-error.log");
 * 
 */

require_once 'vendor/autoload.php';

$foldersToLook = APP_DIR."models/"
        .PATH_SEPARATOR.APP_DIR."controllers/"
        .PATH_SEPARATOR.APP_DIR."core/";

set_include_path(get_include_path().PATH_SEPARATOR.$foldersToLook);

spl_autoload_register(function($class) {
    if ( stream_resolve_include_path("{$class}.php") !== false )  {
        include_once "{$class}.php";
    }
});

Application::run();

