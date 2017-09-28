<?php
namespace SSMysqlManager{
	use SSMysqlManager\utils\Terminal;
	use SSMysqlManager\utils\TextFormat;
	use SSMysqlManager\utils\Logger;
	use SSMysqlManager\command\Command;
    use SSMysqlManager\service\MysqlManager;
    use SSMysqlManager\task\AsyncTask;
    
    require('config.php');
    require('utils/Terminal.php');
    require('utils/TextFormat.php');
    require('utils/Logger.php');
    require('command/Command.php');
    require('service/MysqlManager.php');
    require('task/AsyncTask.php');
	ini_set("allow_url_fopen", 1);
    Terminal::init();
    Logger::setfile("server.log");
    AsyncTask::run(function (){
        Command::run();
    });
    AsyncTask::run(function (){
        MysqlManager::run();
    });
  
}
?>