<?php
class Request
{
    private $properties;
    private $feedback = array();
    
    public function __construct() {
        $this->init();
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
    }
    
    public function addFeedback ($msg)
    {
        array_push($this->feedback, $msg);
    }
    
    public function getFeedback()
    {
        return $this->feedback;
    }
    
    public function getAction()
    {
        $parts = explode("/", $this->getProperty('route'));
        if ( ! empty($parts[1])) {
            $action = str_replace(array(".", "/", "\\"), "", $parts[1]);
            return $action;
        } else { return null; }
    }
}
