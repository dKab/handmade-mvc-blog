<?php

require_once 'vendor/autoload.php';
spl_autoload_register(function($class) {
    if (file_exists("protected/" . $class . ".php" )) {
        include 'protected/' . $class . '.php';
    } elseif ( file_exists("protected/actions/" . $class . ".php") ) {
        include "protected/actions/" . $class . ".php";
    } elseif ( file_exists("protected/transactions/" . $class . ".php") ) {
        include "protected/transactions/" . $class . ".php";
    }
});
Application::run();

