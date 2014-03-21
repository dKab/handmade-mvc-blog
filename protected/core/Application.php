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
            ini_set('display_errors', 'Off');
        } else {
            ini_set('display_errors',1);
            error_reporting(-1);
        }   
    }

    private function handleRequest() {
        $request = new Request();
        $dispatcher = new Dispatcher();
        try {
            $controller = $dispatcher->getController($request);
            $action = $dispatcher->getAction($request);
            $controller->execute($action);
        } catch (NotFoundException $ex) {
                header('HTTP/1.0 404 Not Found');
                echo "<h1>404 Not Found</h1>";
                echo "The page you have requested could not be found.";
                exit();
        }
    }
}
