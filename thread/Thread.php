<?php
namespace SSMysqlManager;

abstract class Thread extends \Thread{

	protected $isKilled = false;

	public function start(int $options = PTHREADS_INHERIT_ALL){
		ThreadManager::getInstance()->add($this);

		if(!$this->isRunning() and !$this->isJoined() and !$this->isTerminated()){
			return parent::start($options);
		}

		return false;
	}

	public function quit(){
		$this->isKilled = true;

		if(!$this->isJoined()){
			if(!$this->isTerminated()){
				$this->join();
			}
		}

		ThreadManager::getInstance()->remove($this);
	}

	public function getThreadName(){
		return (new \ReflectionClass($this))->getShortName();
	}
}
