<?php
namespace SSMysqlManager\utils;
use LogLevel;
class Logger{
    protected static $logFile;

	public function emergency($message){
		self::send($message, "EMERGENCY", TextFormat::RED);
	}

	public function alert($message){
		self::send($message, "ALERT", TextFormat::RED);
	}

	public function critical($message){
		self::send($message, "CRITICAL", TextFormat::RED);
	}

	public function error($message){
		self::send($message, "ERROR", TextFormat::DARK_RED);
	}

	public function warning($message){
		self::send($message, "WARNING", TextFormat::YELLOW);
	}

	public function notice($message){
		self::send($message, "NOTICE", TextFormat::AQUA);
	}

	public function info($message){
		self::send($message, "INFO", TextFormat::WHITE);
	}

	public function debug($message){
		if(self::logDebug === false){
			return;
		}
		self::send($message, "DEBUG", TextFormat::GRAY);
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
        self::logfile($cleanMessage);
	}
    
    public function logfile($message){
		$logResource = fopen(self::$logFile, "a+b");
		if(!is_resource($logResource)){
			self::error("Couldn't open log file");
		}
        fwrite($logResource, $message);
        fwrite($logResource, "\r\n");
		fclose($logResource);
	}
    
    public static function setfile($file){
        self::$logFile = $file;
    }
}