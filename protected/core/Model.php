<?php

class Model {

    protected $dbh;
    protected $stmts = array();
    protected $error;

    public function getError() {
        return $this->error;
    }

    public function __construct() {
        $dbh = AppHelper::instance()->getConnection();
        $this->dbh = $dbh;
    }

    public function prepareStatement($stmt_s) {
        if (isset($this->stmts[$stmt_s])) {
            return $this->stmts[$stmt_s];
        }
        $stmt_handle = $this->dbh->prepare($stmt_s);
        $this->stmts[$stmt_s] = $stmt_handle;
        return $stmt_handle;
    }

    protected function doStatement($stmt_s, $values_a = null) {
        $sth = $this->prepareStatement($stmt_s);
        $sth->closeCursor();
        if ($values_a) {
            $db_result = $sth->execute($values_a);
        } else {
            $db_result = $sth->execute();
        }
        return $sth;
    }

}
