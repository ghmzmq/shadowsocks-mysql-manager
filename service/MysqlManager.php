<?php
namespace SSMysqlManager\service;

use SSMysqlManager\utils\Terminal;
use SSMysqlManager\utils\TextFormat;
use SSMysqlManager\utils\Logger;
use SSMysqlManager\command\Command;
use SSMysqlManager\service\SSPortManager;
use SSMysqlManager\Thread;

class MysqlManager{
    private static $instance = null;
	private static $sleeper = null;
    public $status = false;
    public $results = array();
	public $isRunning = true;
    private $logger;
    private $console;
    private $PortManager;
    private $SocketRev;
    public $socket;
    private $tickCounter;
	private $nextTick = 0;
	private $tickAverage = [20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20];
	private $useAverage = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
	private $maxTick = 20;
	private $maxUse = 0;
    private $dispatchSignals = false;
    
    public function getLogger(){
		return $this->logger;
	}
    
    public function getConsole(){
		return $this->console;
	}
    
    public function getSocket(){
		return $this->socket;
	}
 
    public function getStatus(){
		return $this->isRunning;
	}
    
    public function isRunning(){
		return $this->isRunning === true;
	}
   
    public function shutdown(){
        $this->getlogger()->info("Stopping Server");
        $this->console->shutdown();
        $this->PortManager->shutdown();
		$this->isRunning = false;
	}
    
    public function forceShutdown(){
		try{
			$this->shutdown();
			gc_collect_cycles();
		}catch(\Throwable $e){
			$this->logger->emergency("Crashed while crashing, killing process");
			$this->logger->emergency(get_class($e) . ": ". $e->getMessage());
			@kill(getmypid());
		}
	}
    
    public function checkConsole(){
		if(($line = $this->console->getLine()) !== null){
			$this->prasecommand($line);
		}
	}
    
    public function prasecommand($command){
        $command = explode(" ",$command);      
        switch($command[0]){
            case "help":
                $help = "Helps:\r\n help See helps\r\n stop Stop Server";
                $this->getLogger()->info(TextFormat::AQUA.$help);
                break;
            case "stop":
                $this->getLogger()->info(TextFormat::YELLOW."Stopping Server!");
                $this->shutdown();
                break;
            case "kill":
                $this->getLogger()->info(TextFormat::YELLOW."Killing Server!");
                self::selfkill(getmypid());
                break;
            default:
                $this->getLogger()->info(TextFormat::RED."Command Not Found!Use help for help");
                break;
        }
    }
    
    public function selfkill($pid){
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
    
    public function __construct($logger){
		self::$instance = $this;
		self::$sleeper = new \Threaded;
        $this->logger = $logger;
        $logger->info("SSMysqlManager Started!");
        $this->console = new Command($logger);
        $socket = socket_create( AF_INET, SOCK_DGRAM, SOL_UDP );
        switch(ServerType){
            case "ss":
                $this->PortManager = new SSPortManager($this,$logger,$socket);
                break;
            default:
                $this->getLogger()->critical("Please set a ServerType in config.php");
                $this->getServer()->getConsole()->Addline('stop');
                return;
                break;
        }
        $logger->info("Console Started!");
        $this->start();
    }
    
    public function start(){
        if(function_exists("pcntl_signal")){
			pcntl_signal(SIGTERM, [$this, "handleSignal"]);
			pcntl_signal(SIGINT, [$this, "handleSignal"]);
			pcntl_signal(SIGHUP, [$this, "handleSignal"]);
			$this->dispatchSignals = true;
		}
        $this->tickProcessor();
    }
    
    public function handleSignal($signo){
		if($signo === SIGTERM or $signo === SIGINT or $signo === SIGHUP){
			$this->shutdown();
		}
	}
    
    private function tickProcessor(){
		$this->nextTick = microtime(true);
		while($this->isRunning){
			$this->tick();
			$next = $this->nextTick - 0.0001;
			if($next > microtime(true)){
				try{
					time_sleep_until($next);
				}catch(\Throwable $e){
				}
			}
		}
	}
    
    private function titleTick(){
		if(!Terminal::hasFormattingCodes()){
			return;
		}

		echo "\x1b]0;SSMysqlManager\x07";
	}
    
    private function tick(){
		$tickTime = microtime(true);
		if(($tickTime - $this->nextTick) < -0.025){ //Allow half a tick of diff
			return false;
		}

		++$this->tickCounter;
        
        $this->checkConsole();
        
		if(($this->tickCounter & 0b1111) === 0){
			$this->titleTick();
			$this->maxTick = 20;
			$this->maxUse = 0;
		}

		if($this->dispatchSignals and $this->tickCounter % 5 === 0){
			pcntl_signal_dispatch();
		}

		$now = microtime(true);
		$tick = min(20, 1 / max(0.001, $now - $tickTime));
		$use = min(1, ($now - $tickTime) / 0.05);

		if($this->maxTick > $tick){
			$this->maxTick = $tick;
		}

		if($this->maxUse < $use){
			$this->maxUse = $use;
		}

		array_shift($this->tickAverage);
		$this->tickAverage[] = $tick;
		array_shift($this->useAverage);
		$this->useAverage[] = $use;

		if(($this->nextTick - $tickTime) < -1){
			$this->nextTick = $tickTime;
		}else{
			$this->nextTick += 0.05;
		}

		return true;
	}
    
    public function ManageIptableRules($ports,$type){
        foreach($ports as $res){
            switch($type){
                case "add":
                    $intcpcommand = "iptables -A INPUT -p tcp --dport " . $res['port'];
                    $ottcpcommand = "iptables -A OUTPUT -p tcp --sport " . $res['port'];
                    $inudpcommand = "iptables -A INPUT -p udp --dport " . $res['port'];
                    $otudpcommand = "iptables -A OUTPUT -p udp --sport " . $res['port'];
                    break;
                case "del":
                    $intcpcommand = "iptables -D INPUT -p tcp --dport " . $res['port'];
                    $ottcpcommand = "iptables -D OUTPUT -p tcp --sport " . $res['port'];
                    $inudpcommand = "iptables -D INPUT -p udp --dport " . $res['port'];
                    $otudpcommand = "iptables -D OUTPUT -p udp --sport " . $res['port'];
                    break;
                default:
                    break;
            }
            `$intcpcommand`;
            `$inudpcommand`;
            `$ottcpcommand`;
            `$otudpcommand`;
            $this->logger->debug("Rule For Port " . $res['port'] . " Done!");
        }
    }
    
    public function PraseIptables($type){
        switch($type){
            case "input":
                $file = `iptables -nvx -L INPUT`;
                break;
            case "output":
                $file = `iptables -nvx -L OUTPUT`;
                break;
            default:
                return; 
        }
        $file = explode("\n", trim($file));
        $file = array_splice($file,2);
        $successarray = array();
        foreach($file as $fi){
            $fii = explode(" ", $fi);
            $newfi = array();
            foreach($fii as $fi){
                if($fi !=  ""){
                    $newfi[] = $fi;
                }
            }
            $successarray[] = $newfi;
        }
        return $successarray;
    }
    
    public function getUpdateData($iarray){
        $retu = array();
        foreach($iarray as $array){
            preg_match_all('/(\d{5}(\.\d+)?)/is',$array[9],$port);
            $port = $port[0][0];
            if($port){
                $transfer = $array[1];
            }
            if(!isset($retu[$port])){
                $retu[$port] = array(
                    "port" => $port,
                    "transfer" => $transfer
                );
            }else{
                $retu[$port]['transfer'] = $retu[$port]['transfer'] + $transfer;
            }
        }
        return $retu;
    }
    
    public function PraseMysql($in,$out){
        $mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if(!$mysql){
            $this->getLogger()->critical("Can't connect to MYSQL Server");
        }
        foreach($in as $pp){
            $this->getLogger()->debug("Port " . $pp['port'] . " Uploaded " . $pp['transfer'] . " bytes");
            $sql = "UPDATE `user` SET t=".time().", u=u+".($pp['transfer']*trafficrate)." WHERE port=" .$pp['port'];
            $result = $mysql->query($sql);
            if(!$result){
                $this->getLogger()->critical("Update Port " . $pp['port'] . " Failed");
            }
        }
        foreach($out as $pp){
            $this->getLogger()->debug("Port " . $pp['port'] . " downloaded " . $pp['transfer'] . " bytes");
            $sql = "UPDATE `user` SET t=".time().", d=d+".($pp['transfer']*trafficrate)." WHERE port=" .$pp['port'];
            $result = $mysql->query($sql);
            if(!$result){
                $this->getLogger()->critical("Update Port " . $pp['port'] . " Failed");
            }
        }
        mysqli_close($mysql);
        return true;
    }
    
    public function ClearIptables(){
        $command = "iptables -Z";
        `$command`;
    }
    
    public function CheckStatus(){
        $getarray = $this->PraseIptables('input');
        $inresults = $this->getUpdateData($getarray);
        $getarray = $this->PraseIptables('output');
        $otresults = $this->getUpdateData($getarray);
        $this->ClearIptables();
        $this->PraseMysql($inresults,$otresults);
    }
  
}
