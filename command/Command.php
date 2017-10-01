<?php
namespace SSMysqlManager\command;

use SSMysqlManager\Thread;
use SSMysqlManager\utils\TextFormat;
use SSMysqlManager\utils\Terminal;

class Command extends Thread{
	private $readline;
	protected $buffer;
	private $shutdown = false;
    private $logger;
    
    public function getLogger(){
		return $this->logger;
	}
    
    public function __construct($logger){
        $this->logger = $logger;
		$this->buffer = new \Threaded;
        $this->start();
	}
    
	private function readLine(){
		if(!$this->readline){
			global $stdin;

			if(!is_resource($stdin)){
				return "";
			}

			return trim(fgets($stdin));
		}else{
			$line = trim(readline("> "));
			if($line != ""){
				readline_add_history($line);
			}

			return $line;
		}
	}

	public function getLine(){
		if($this->buffer->count() !== 0){
			return $this->buffer->shift();
		}

		return null;
	}

	public function run(){
		$opts = getopt("", ["disable-readline"]);
		if(extension_loaded("readline") and !isset($opts["disable-readline"])){
			$this->readline = true;
		}else{
			global $stdin;
			$stdin = fopen("php://stdin", "r");
			stream_set_blocking($stdin, 0);
			$this->readline = false;
		}
        $this->getlogger()->info(TextFormat::GREEN."SSMysqlManager's Terminal Started!");
		$lastLine = microtime(true);
		while(!$this->shutdown){
			if(($line = $this->readLine()) !== ""){
				//$this->buffer[] = preg_replace("#\\x1b\\x5b([^\\x1b]*\\x7e|[\\x40-\\x50])#", "", $line);
                $this->buffer[] = preg_replace("#\\x1b\\x5b([^\\x1b]*\\x7e|[\\x40-\\x50])#", "", $line);
			}
			$lastLine = microtime(true);
		}
	}
    
    public function Addline($command){
        $this->buffer[] = preg_replace("#\\x1b\\x5b([^\\x1b]*\\x7e|[\\x40-\\x50])#", "", $line);
    }
    
    public function getStatus(){
		return $this->shutdown;
	}
    
    public function shutdown(){
		$this->shutdown = true;
	}

    public function getThreadName(){
		return "Console";
	}
    
}
