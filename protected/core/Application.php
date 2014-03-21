<?php

class Application {

    //private $appHelper;

    private function __construct() {
        
    }

    public static function run() {
        $instance = new self();
        $instance->init();
        $instance->handleRequest();
    }

    public function init() {
        $appHelper = AppHelper::instance();
        $appHelper->init();
        if ($appHelper->isProduction()) {
            //error_reporting(0);
            ini_set("log_errors", 1);
            ini_set("error_log", "log/php-error.log");
            //error_log( "error logging enabled!" );
             trigger_error("fsdf", E_USER_ERROR); //тест
        } else {
            error_reporting(E_ALL);
        }
                   // echo ini_get('log_errors');
         
    }

    private function handleRequest() {
        $request = new Request();
        $dispatcher = new Dispatcher();
        try {
            $controller = $dispatcher->getController($request);
            $action = $dispatcher->getAction($request);
            $controller->execute($action);
        } catch (NotFoundException $ex) {
            if ($ex instanceof NotFoundException) {
                header('HTTP/1.0 404 Not Found');
                echo "<h1>404 Not Found</h1>";
                echo "The page that you have requested could not be found.";
                exit();
            } elseif (AppHelper::isProduction()) {
                header('HTTP/1.0 500 Internal Server Error');
                echo "<h1>Oops! It seems an error has occured.</h1>"
                . " The administrator of the site will get a notification. Sorry!";
                exit();
                #or maybe output something prettier 
            }
        }
    }

}
