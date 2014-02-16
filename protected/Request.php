<?php
class Request
{
    private $properties;
    private $feedback = array();
    
    public function __construct() {
        $this->init();
        AppHelper::setRequest($this);
    }
    
    private function init()
    {
        if (isset( $_SERVER['REQUEST_METHOD'] ) ) {
            $this->properties = $_REQUEST;
            $this->setProperty('REQUEST_URI', $_SERVER['REQUEST_URI']);
            return;
        }
        //TODO may add console args if there launched in batch mode
    }
    
    public function getProperty($key)
    {
        if (isset($this->properties[$key])) {
            return $this->properties[$key];
        }
    }
    
    public function setProperty($key, $val)
    {
        $this->properties[$key] = $val;
        return $this;
    }
    
    public function addFeedback ($msg)
    {
        array_push($this->feedback, $msg);
    }
    
    public function getFeedback()
    {
        return $this->feedback;
    }
    
    public function getRoute()
    {
        $route = array();
        $parts = explode("/", $this->getProperty('REQUEST_URI'));
        for ($i=1; $i< min(count($parts), 3); $i++) {
            if ( ! empty($parts[$i])) {
                $parts[$i] = str_replace(array(".", "\\"), "", $parts[$i]);
                $parts[$i] = preg_replace("/(\\w)\\?.*/ui", "{1}", $parts[$i]);
                $route[] = $parts[$i];
            }
        }
        return $route;
    }
    
}
