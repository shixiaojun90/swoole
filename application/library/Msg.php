<?php
class Msg extends Model{
	public static $instance;
	public $client;
	public function __construct($str='zys_message') {
		parent::__construct($str);
		$host = '127.0.0.1';
    	$prot = 8080;
    	$this->client = new WebSocketClient($host, $prot);
    	$this->client->connect();
	}

	public function sendtask($data){
		$this->client->send($data);
		//self::finish();
	}

	public function finish(){
		$this->client->disconnect();
	}
	 public static function getInstance() {
        if (!(self::$instance instanceof Msg)) {
            self::$instance = new Msg;
        }
        return self::$instance;
    }




}
