<?php
mb_internal_encoding('utf-8');

define('APP_DIR', "./protected/");

require_once 'vendor/autoload.php';

$foldersToLook = APP_DIR."models/"
        .PATH_SEPARATOR.APP_DIR."controllers/"
        .PATH_SEPARATOR.APP_DIR."core/";


function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    if (!(error_reporting() & $errno)) {
        return;
    }
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);   
}

set_error_handler("exception_error_handler");


set_include_path(get_include_path().PATH_SEPARATOR.$foldersToLook);

spl_autoload_register(function($class) {
    if ( stream_resolve_include_path("{$class}.php") !== false )  {
        include_once "{$class}.php";
    }
});
Application::run();
