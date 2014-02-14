<?php
class Application
{
    private $appHelper;
    
    private function __construct() {}
    
    public static function run()
    {
        $instance = new self();
        $instance->init();
        $instance->handleRequest();
    }
    
    public function init()
    {
        $appHelper = AppHelper::instance();
        $appHelper->init();
    }
    
    private function handleRequest()
    {
        $request = new Request();
        $controller = new Controller();
        $action = $controller->getAction($request);
        $action->execute($request);
    }
}

