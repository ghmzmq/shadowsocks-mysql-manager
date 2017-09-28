<?php
namespace SSMysqlManager\service;

use SSMysqlManager\utils\Logger;
use SSMysqlManager\command\Command;

class MysqlManager{
    public static $shutdown = false;
    public static $status = false;
    public static $mysql;
    public static $results = array();
    
    public function run(){
        while(!self::$shutdown){
            if(!self::$status){
                self::$mysql = mysql_connect(DB_HOST, DB_USER, DB_PASS);
                if(!self::$mysql){
                    Logger::critical("Can't connect to MYSQL Server");
                    Command::prasecommand('/stop');
                }
                mysql_select_db(DB_NAME);
                $uasql = "Select * FROM `user` WHERE `enable` = 1";
                $result = mysql_query($uasql,self::$mysql);
                $results = array();
                while($row = mysql_fetch_array($result,MYSQL_ASSOC)){
                    $results[] = $row;
                }
                if(!empty($results)){
                    Logger::info("Starting Servers");
                    self::$results = $results;
                    foreach($results as $res){
                        switch(ServerType){
                            case "ss":
                                $command = "ss-server -p ". $res['port'] . " -k " . $res['passwd'] . " -m " . method;
                                break;
                            case "ssr":
                                $command = "ss-server -p ". $res['port'] . " -k " . $res['passwd'] . " -m " . method;
                                Logger::critical("Please Change to Shadowsocks");
                                break;
                            default:
                                Logger::critical("Please set a ServerType in config.php");
                                Command::prasecommand('/stop');
                                break;
                        }
                        Logger::info("Opening " . $res['port'] . ",Method " . method);
                        `$command`;
                    }
                }
                self::$status = true;
            }
        }
    }
    
    public function getisset($port){
        foreach(self::$results as $result){
            if($result['port'] == $port){
                return true;
            }
        }
        return false;
    }
}