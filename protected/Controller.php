<?php
class Controller
{
    private static $defaultAction;
    
    public function __construct()
    {
        require_once 'actions/DefaultAction.php';
        self::$defaultAction = new DefaultAction();
    }
    
    public function getAction(Request $request)
    {
        $action = $request->getAction();
        $sep = DIRECTORY_SEPARATOR;
        if ( ! $action ) {
            return self::$defaultAction;
        }
        $filepath="protected{$sep}actions{$sep}" . ucfirst($action) . "Action.php";
        $classname=  ucfirst($action) . "Action";
        if (file_exists($filepath)) {
            require_once "$filepath";
            if ( class_exists($classname) && $classname instanceof Action) {
                return new $classname();
            } else {
                return false;
                //TODO redirect to 404 page
            }
        }
        return clone self::$defaultAction;
    }
}

