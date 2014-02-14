<?php
spl_autoload_register(function($class) {
    include 'protected/' . $class . '.php';
});
Application::run();

