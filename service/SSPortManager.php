<?php
namespace SSMysqlManager\service;

use SSMysqlManager\Thread;

class SSPortManager extends Thread{
    private $status = false;
    private $results;
    private $logger;
    private $shutdown = false;
    private $socket;
    private $timeline;
    private $tick = 0;
    
    public function shutdown(){
        $this->getServer()->ManageIptableRules(json_decode($this->results,true),'del');
		$this->shutdown = true;
	}
    
    public function getLogger(){
		return $this->logger;
	}
    
    public function getServer(){
		return $this->server;
	}
 
    public function __construct($server,$logger,$socket){
        $this->server = $server;
        $this->socket = $socket;
        $this->logger = $logger;
        $this->getLogger()->info("Start Listening!");
        $this->start();
    }
    
    public function run(){
        $this->timeline = time();
        while(!$this->shutdown){
            if(!$this->status){
                $mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                if(!$mysql){
                    $this->getLogger()->critical("Can't connect to MYSQL Server");
                    $this->getServer()->getConsole()->Addline('stop');
                }
                $uasql = "Select * FROM `user` WHERE `enable` = 1";
                $result = $mysql->query($uasql);
                mysqli_close($mysql);
                $results = array();
                while($row = mysqli_fetch_assoc($result)){
                    $results[$row['port']] = $row;
                }
                if(!empty($results)){
                    $this->getLogger()->info("Starting Server");
                    $this->results = json_encode($results);
                    $command = "ssserver -p 2333 -k 2333 -m " . method . "  --manager-address 0.0.0.0:6001 -d start";
                    `$command`;
                    $json = array(
                        'server_port' => '2333'
                    );
                    $json = 'remove:'.json_encode($json);
                    $socket = $this->socket;
                    if ($socket === false){
                        $this->getLogger()->critical("socket_create() failed:reason:" . socket_strerror(socket_last_error()));
                        $this->getServer()->getConsole()->Addline('stop');
                        return;
                    }
                    $len = strlen($json);
                    socket_sendto($socket, $json, $len, 0, '0.0.0.0', 6001);
                    $this->Socketrev($socket);
                    $this->getLogger()->info("Adding iptable Rules");
                    $this->getServer()->ManageIptableRules($results,'add');
                    $this->getLogger()->info("Adding Ports");
                    foreach($results as $res){
                        $json = $this->getJson($res['port'],$res['passwd'],"add");
                        if(!debugmode){
                            $this->getLogger()->info("Opening " . $res['port']);
                        }else{
                            $this->getLogger()->info("Opening " . $res['port'] . ",Method " . method . " Password " . $res['passwd']);
                        }
                        $len = strlen($json);
                        socket_sendto($socket, $json, $len, 0, '0.0.0.0', 6001);
                        $this->Socketrev($socket);
                    }
                }
                $this->status = true;
            }else{
                if(!$this->shutdown){
                    $this->tick ++;
                    if($this->tick == 15){
                        $this->tick = 0;
                        $this->getServer()->CheckStatus();
                        $this->results = $this->CheckNewAccountAndOutdateAccount($this->results);
                        $this->getLogger()->info("Get Traffic Done!");
                    }
                    sleep(1);
                }
            }
        }
    }
    
    public function CheckNewAccountAndOutdateAccount($results){
        $resulte = json_decode($results,true);
        $mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if(!$mysql){
            $this->getLogger()->critical("Can't connect to MYSQL Server");
        }
        $uasql = "Select * FROM `user` WHERE `enable` = 1";
        $result = $mysql->query($uasql);
        mysqli_close($mysql);
        $results = array();
        while($row = mysqli_fetch_assoc($result)){
            $results[] = $row;
        }
        $socket = $this->socket;
        if(!empty($results)){
            foreach($results as $res){
                $trafficused = $res['u'] + $res['d'];
                if(!isset($resulte[$res['port']])){
                    if($trafficused < $res['transfer_enable']){
                        $this->getLogger()->info("New Port Detected,Port: ".$res['port']);
                        $json = $this->getJson($res['port'],$res['passwd'],"add");
                        if(!debugmode){
                            $this->getLogger()->info("Opening " . $res['port']);
                        }else{
                            $this->getLogger()->info("Opening " . $res['port'] . ",Method " . method . " Password " . $res['passwd']);
                        }
                        $len = strlen($json);
                        socket_sendto($socket, $json, $len, 0, '0.0.0.0', 6001);
                        $this->Socketrev($socket);
                    }
                }
                if(isset($resulte[$res['port']]) and $trafficused > $res['transfer_enable']){
                    $this->getLogger()->info("Detected Overused Port: ".$res['port']);
                    unset($resulte[$res['port']]);  
                    $json = $this->getJson($res['port'],$res['passwd'],"del");
                    $len = strlen($json);
                    socket_sendto($socket, $json, $len, 0, '0.0.0.0', 6001);
                    $this->Socketrev($socket);
                }
            }
        }
        return json_encode($resulte,true);
    }
    
    public function getJson($port,$password,$command){
        switch($command){
            case "add":
                $json = array(
                    'server_port' => (int)$port,
                    'password' => $password
                );
                $json = 'add:'.json_encode($json,true);
                break;
            case "del":
                $json = array(
                    'server_port' => (int)$port,
                );
                $json = 'remove:'.json_encode($json,true);
                break;
        }
        return $json;
    }
    
    public function Socketrev($socket){
        $from = "";
        $port = 0;
        socket_recvfrom($socket, $buf,1024, 0, $from, $port );
        if($buf){
            $this->getLogger()->debug("Receive:" . $buf);  
            return $buf;
        }
    }
    
    public function getisset($port){
        $resultss = json_decode($this->results,true);
        foreach($resultss as $result){
            if($result['port'] == $port){
                return true;
            }
        }
        return false;
    }
  
}