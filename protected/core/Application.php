<?php

class Application {

    private $appHelper;
    
    const PRODUCTION = 2;
    const DEVELOPMENT = 1;

    private function __construct() {
        
    }
    
    private static function isProduction() {
        return ( (int) AppHelper::instance()->getMode() === self::PRODUCTION );
    }

    public static function run() {
        $instance = new self();
        $instance->init();
        $instance->handleRequest();
    }

    public function init() {
        $appHelper = AppHelper::instance();
        $appHelper->init();
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
            }  elseif($this->isProduction()) {
                header('HTTP/1.0 500 Internal Server Error');
                echo "<h1>Oops! It seems an error has occured.</h1>"
                . " The administrator of the site will get a notification. Sorry!";
                exit();
                #or maybe output something prettier 
            }
        }
    }

}
