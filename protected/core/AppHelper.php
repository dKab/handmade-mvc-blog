<?php

class AppHelper {
    
    const PRODUCTION = 2;
    const DEVELOPMENT = 1;

    public function isProduction() {
        return ( (int) $this->options->stage === self::PRODUCTION );
    }
    
    private static $instance;
    private $config = "protected/config/config.xml";
    private $options;
    private static $twig;
    private static $request;
    private $connection;

    private function __construct() {
        
    }

    static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        $this->ensure(file_exists($this->config), "Файл конфигурации не найден");
        $options = simplexml_load_file($this->config);
        $this->ensure($options instanceof SimpleXMLElement, "Файл конфигурации запорчен");
        $this->options = $options;
    }

    private function ensure($expr, $message) {
        if (!$expr) {
            throw new Exception($message);
        }
    }

    public function postsPerPage() {
        return $this->options->pagination->posts->limit;
    }

    public function commentsPerPage() {
        return $this->options->pagination->comments->limit;
    }
    public function getDomainName() {
        return $this->options->domain->name;
    }

    public function getConnection() {
        if (!isset($this->connection)) {
            $host = $this->options->db->host;
            $name = $this->options->db->name;
            $user = $this->options->db->user;
            $password = $this->options->db->password;

            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            );

            $connection = new PDO("mysql:host={$host};dbname={$name}", $user, $password, $options);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection = $connection;
        }
        return $this->connection;
    }

    public function getCutTag() {
        return $this->options->post->cut;
    }
    public function getVideoTag() {
        return $this->options->post->videoPlaceHolder;
    }

    public function getDefaultCategory() {
        return $this->options->post->defaultCategory;
    }

    public function getUserSign($user) {
        return $this->options->user->$user->sign;
    }

    public function getUserEmail($user) {
        return $this->options->user->$user->email;
    }

    public function getCommentRule() {
        return $this->options->comments->moderate;
    }
    public function getMode() {
        return $this->options->stage;
    }

    public static function twig() {
        $templateDir = "protected/views";
        $layoutDir = $templateDir . "/layouts";
        if (!isset(self::$twig)) {
            $loader = new Twig_Loader_Filesystem(array($templateDir, $layoutDir));
            self::$twig = new Twig_Environment($loader, array(
                'debug' => true
            ));
            self::$twig->addExtension(new Twig_Extension_Debug());
        }
        return self::$twig;
    }

    public static function getRequest() {
        return self::$request;
    }

    public static function setRequest(Request $request) {
        self::$request = $request;
    }

}
