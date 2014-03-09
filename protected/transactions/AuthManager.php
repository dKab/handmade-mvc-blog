<?php

class AuthManager extends Transaction {

    public function __construct() {
        parent::__construct();
    }

    private static $select = "SELECT name FROM user WHERE name = :name AND password = MD5(:password)";

    public function login(Array $params) {
        $sth = $this->doStatement(self::$select, array(
            'name' => $params['name'],
            'password' => $params['password'],
        ));
        $name = $sth->fetchColumn();
        return $name;
    }

}
