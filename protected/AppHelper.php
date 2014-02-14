<?php
class AppHelper {
    
    private static $instance;
    private $config = "protected/config/config.xml";
    private $options;
    
    //private $transactionsDir = "transactions";
    
    private $connection;

    private function __construct(){ }
    
    static function instance()
    {
        if ( ! self::$instance ) {
            self::$instance = new self(); 
        }
        return self::$instance;
    }
    
    public function init()
    {
        $this->ensure( file_exists($this->config),
                "Файл конфигурации не найден");
        $options = simplexml_load_file( $this->config );
        $this->ensure( $options instanceof SimpleXMLElement,
                "Файл конфигурации запорчен");
        $this->options = $options;
    }
    
    private function ensure ( $expr, $message )
    {
        if ( ! $expr ) {
            throw new Exception( $message );
        }
    }

    public function getConnection()
    {
        if ( ! isset($this->connection) ) {
            $host = $this->options->db->host;
            $name = $this->options->db->name;
            $user = $this->options->db->user;
            $password = $this->options->db->password;
        
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            );
        
            $connection = new PDO("mysql:host={$host};dbname={$name}", $user, $password, $options);
            $this->connection = $connection;
        }
        return $this->connection;   
    }
    
}
