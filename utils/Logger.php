<?php
namespace SSMysqlManager\utils;
use LogLevel;
class Logger{
    protected $logFile;

    public static $logger = null;

	public function __construct($logFile){
		if(static::$logger instanceof Logger){
			throw new \RuntimeException("MainLogger has been already created");
		}
		static::$logger = $this;
		touch($logFile);
		$this->logFile = $logFile;
	}
    
    public static function getLogger(){
		return static::$logger;
	}
    
	public function emergency($message){
		$this->send($message, "EMERGENCY", TextFormat::RED);
	}

	public function alert($message){
		$this->send($message, "ALERT", TextFormat::RED);
	}

	public function critical($message){
		$this->send($message, "CRITICAL", TextFormat::RED);
	}

	public function error($message){
		$this->send($message, "ERROR", TextFormat::DARK_RED);
	}

	public function warning($message){
		$this->send($message, "WARNING", TextFormat::YELLOW);
	}

	public function notice($message){
		$this->send($message, "NOTICE", TextFormat::AQUA);
	}

	public function info($message){
		$this->send($message, "INFO", TextFormat::WHITE);
	}

	public function debug($message){
		$this->send($message, "DEBUG", TextFormat::GRAY);
	}
    
    protected function send($message, $prefix, $color){
		$now = time();

		$message = TextFormat::toANSI(TextFormat::AQUA . "[" . date("H:i:s", $now) . "] ". TextFormat::RESET . $color ."[SSMysqlManager/" . $prefix . "]:" . " " . $message . TextFormat::RESET);
		$cleanMessage = TextFormat::clean($message);

		if(!Terminal::hasFormattingCodes()){
			echo $cleanMessage . PHP_EOL;
		}else{
			echo $message . PHP_EOL;
		}
        $this->logfile($cleanMessage);
	}
    
    public function logfile($message){
		$logResource = fopen($this->logFile, "a+b");
		if(!is_resource($logResource)){
			$this->error("Couldn't open log file");
		}
        fwrite($logResource, $message);
        fwrite($logResource, "\r\n");
		fclose($logResource);
	}
    
    public static function setfile($file){
        $this->logFile = $file;
    }
}