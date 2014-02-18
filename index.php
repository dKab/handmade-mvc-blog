<?php
mb_internal_encoding('utf-8');
require_once 'vendor/autoload.php';
spl_autoload_register(function($class) {
    if (file_exists("protected/" . $class . ".php" )) {
        include 'protected/' . $class . '.php';
    } elseif ( file_exists("protected/controllers/" . $class . ".php") ) {
        include "protected/controllers/" . $class . ".php";
    } elseif ( file_exists("protected/transactions/" . $class . ".php") ) {
        include "protected/transactions/" . $class . ".php";
    }
});
Application::run();

