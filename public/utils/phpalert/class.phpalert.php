<?php

class PHPAlert {

    private $uri;
    
    // Construct Method. Initiate a session and register root uri for the gadget.
    public function __construct($uri) {
        session_start();
        $this->uri = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off" ? "https" : "http")."://".$_SERVER["SERVER_NAME"].$uri."/phpalert/";
    }

    // Register a new alert in the queue.
    public function add($msg, $type = "warning") {
        $_SESSION['alerts'][] = (object) array(
                    "type" => $type,
                    "msg" => $msg
        );
    }

    // Show all alerts registered in the queue.
    public function show() {
        $uri = $this->uri;
        
        include __DIR__ . "/alert.php";

        if (empty($_SESSION['alerts']))
            unset($_SESSION['alerts']);
    }

}

?>