<?php
namespace SSMysqlManager{
	use SSMysqlManager\utils\Terminal;
	use SSMysqlManager\utils\Logger;
    use SSMysqlManager\service\MysqlManager;
    
    require('thread/Thread.php');
    require('thread/ThreadManager.php');
    require('config.php');
    require('utils/Terminal.php');
    require('utils/TextFormat.php');
    require('utils/Logger.php');
    require('command/Command.php');
    require('service/MysqlManager.php');
    require('service/PortManager.php');
	ini_set("allow_url_fopen", 1);
    ini_set("date.timezone", "UTC");
    Terminal::init();
    $logger = new Logger("server.log");
    $pthreads_version = phpversion("pthreads");
	if(substr_count($pthreads_version, ".") < 2){
		$pthreads_version = "0.$pthreads_version";
	}
	if(version_compare($pthreads_version, "2.0.9") < 0){
		$logger->critical("pthreads >= 2.0.9 is required, while you have $pthreads_version.");
        exit(1);
	}
    ThreadManager::init();
    $manager = new MysqlManager($logger);
    
    $logger->info("Stopping Server");
    
    switch(ServerType){
        case "ss":
            $command = "ssserver -d stop";
            $logger->info("SSServer Stopped");
            `$command`;
            break;
        case "ssr":
            break;
        default:
            break;
    }
	$logger->info("Stopping other threads");

	foreach(ThreadManager::getInstance()->getAll() as $id => $thread){
		$logger->debug("Stopping " . (new \ReflectionClass($thread))->getShortName() . " thread");
		$thread->quit();
	}
  
}
?>