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
try {
    Application::run();
} catch (Exception $e) {
    $time = new DateTime();
    $message = "[" . $time->format('Y-m-d H:i:sP') . "] " . $e->getMessage()
                        . "\n" . "Код: " . $e->getCode() . "\n" .
                        "Стек: " . $e->getTraceAsString();
    if ( AppHelper::instance()->isProduction() ) {
                // устанавливаем логгирование ошибок
                ini_set("log_errors", 1);
                ini_set("error_log", "log/php-error.log");
                // вручную записываем сообщение в лог
                error_log($message);
                //показываем заглушку для пользователей и завершаем выполнение
                header('HTTP/1.0 500 Internal Server Error');
                echo "<h1>Oops! It seems an error has occured.</h1>"
                . " The administrator of the site will get a notification. Sorry!";
    }  else {
        echo nl2br($message);
    }
    exit();
    
}

