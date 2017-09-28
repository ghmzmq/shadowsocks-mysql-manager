<?php
namespace SSMysqlManager\command;

use SSMysqlManager\utils\Terminal;
use SSMysqlManager\utils\TextFormat;
use SSMysqlManager\utils\Logger;

class Command{
	private static $readline;
	protected $buffer;
	private static $shutdown = false;

	public function shutdown(){
		self::$shutdown = true;
	}

	private function readLine(){
		if(!self::$readline){
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
			self::$readline = true;
		}else{
			global $stdin;
			$stdin = fopen("php://stdin", "r");
			stream_set_blocking($stdin, 0);
			self::$readline = false;
		}
        Logger::info(TextFormat::GREEN."SSMysqlManager's Terminal Started!");
		$lastLine = microtime(true);
		while(!self::$shutdown){
			if(($line = self::readLine()) !== ""){
				//$this->buffer[] = preg_replace("#\\x1b\\x5b([^\\x1b]*\\x7e|[\\x40-\\x50])#", "", $line);
                self::prasecommand($line);
			}
			$lastLine = microtime(true);
		}
	}
    
    public function prasecommand($command){
        $command = explode(" ",$command);      
        switch($command[0]){
            case "/help":
                $help = "Helps:\r\n /help See helps\r\n /stop Stop Server";
                Logger::info(TextFormat::AQUA.$help);
                break;
            case "/stop":
                Logger::info(TextFormat::YELLOW."Stopping Server!");
                self::kill(getmypid());
                break;
            default:
                Logger::info(TextFormat::RED."Command Not Found!Use /help for help");
                break;
        }
    }
    
    function kill($pid){
		switch(Terminal::getOS()){
			case "win":
				exec("taskkill.exe /F /PID " . ((int) $pid) . " > NUL");
				break;
			case "mac":
			case "linux":
			default:
				if(function_exists("posix_kill")){
					posix_kill($pid, SIGKILL);
				}else{
					exec("kill -9 " . ((int)$pid) . " > /dev/null 2>&1");
				}
		}
	}
}
