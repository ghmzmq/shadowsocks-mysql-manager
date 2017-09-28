<?php
namespace SSMysqlManager\task;

use SSMysqlManager\utils\Logger;
use SSMysqlManager\command\Command;

class AsyncTask{
    
    public function __construct(){

    }


    public static function run($call)
    {
        $pid = pcntl_fork();
        if ($pid == -1){
            Logger::critical("fork(1) failed!");
            Command::prasecommand('/stop');
        }elseif ($pid > 0){

        }else{
            //posix_setsid();
            if (is_callable($call)) $call();
            exit;
        }

    }
}