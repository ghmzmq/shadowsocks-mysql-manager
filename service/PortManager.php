<?php
namespace SSMysqlManager\service;

use SSMysqlManager\Thread;

class PortManager extends Thread{
    private $status = false;
    private $results;
    private $logger;
    private $shutdown = false;
    private $socket;
    
    public function shutdown(){
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
        while(!$this->shutdown){
            if(!$this->status){
                $mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                if(!$mysql){
                    $this->getLogger()->critical("Can't connect to MYSQL Server");
                    $this->getServer()->getConsole()->Addline('stop');
                }
                $uasql = "Select * FROM `user` WHERE `enable` = 1";
                $result = $mysql->query($uasql);
                $results = array();
                while($row = mysqli_fetch_assoc($result)){
                    $results[] = $row;
                }
                if(!empty($results)){
                    $this->getLogger()->info("Starting Server");
                    $this->results = json_encode($results);
                    switch(ServerType){
                        case "ss":
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
                            break;
                        case "ssr":
                            $this->getLogger()->critical("Please Change to Shadowsocks");
                            $this->getServer()->getConsole()->Addline('stop');
                            return;
                            break;
                        default:
                            $this->getLogger()->critical("Please set a ServerType in config.php");
                            $this->getServer()->getConsole()->Addline('stop');
                            return;
                            break;
                    }
                    $this->getLogger()->info("Adding Ports");
                    foreach($results as $res){
                        switch(ServerType){
                            case "ss":
                                $json = array(
                                    'server_port' => (int)$res['port'],
                                    'password' => $res['passwd']
                                );
                                $json = 'add:'.json_encode($json);
                                break;
                            case "ssr":
                                $this->getLogger()->critical("Please Change to Shadowsocks");
                                break;
                            default:
                                $this->getLogger()->critical("Please set a ServerType in config.php");
                                $this->console->shutdown();
                                return;
                                break;
                        }
                        $this->getLogger()->info("Opening " . $res['port'] . ",Method " . method . "Password " . $res['passwd']);
                        $len = strlen($json);
                        socket_sendto($socket, $json, $len, 0, '0.0.0.0', 6001);
                        $this->Socketrev($socket);
                    }
                }
                $this->status = true;
            }else{
                $this->getLogger()->critical(2333);
                sleep(5);
            }
        }
    }
    
    public function Socketrev($socket){
        $from = "";
        $port = 0;
        socket_recvfrom($socket, $buf,1024, 0, $from, $port );
        if($buf){
            $this->getLogger()->info("Receive:" . $buf);    
        }
    }
    
    public function getisset($port){
        $resultss = json_decode($this->results);
        foreach($resultss as $result){
            if($result['port'] == $port){
                return true;
            }
        }
        return false;
    }
  
}