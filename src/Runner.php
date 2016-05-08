<?php
namespace ajumamoro;

use ntentan\logger\Logger;
use ntentan\config\Config;

class Runner
{
    /**
     *
     * @var ajumamoro\Store
     */
    private static $jobId;

    /**
     *
     * @param \Exception $exception
     * @param string $prefix
     */
    public static function logException(\Exception $exception, $prefix = "Exception thrown")
    {
        $class = new \ReflectionClass($exception);
        Logger::error("$prefix [{$class->getName()}] {$exception->getMessage()} on line {$exception->getLine()} of {$exception->getFile()}");
        Logger::debug("Debug trace for exception {$exception->getTraceAsString()}");
    }

    public static function deleteJob($job)
    {
        Store::getInstance()->delete($job);
    }

    private static function runJob($job)
    {
      try{
          Logger::notice("Job #" . self::$jobId . " started.");
          $job->setup();
          $job->go();
          $job->tearDown();
          Logger::notice("Job #" . self::$jobId . " finished.");
      }
      catch(\Exception $e)
      {
          self::logException($e, "Job #" . self::$jobId . " Exception");
          Logger::alert("Job #" . self::$jobId . " died.");
      }
    }

    private static function executeJob(Job $job)
    {
        self::$jobId = $job->getId();
        Logger::notice("Recived a new job #" . self::$jobId);
        //Store::getInstance()->markStarted(self::$jobId);

        if(!function_exists('pcntl_fork')) {
            self::runJob($job);
            Logger::notice("Job #" . self::$jobId . " exited.");
            return;
        }

        $pid = pcntl_fork();
        if($pid)
        {
            pcntl_wait($status);
            Logger::notice("Job #" . self::$jobId . " exited.");
            //Store::getInstance()->markFinished(self::$jobId);
            //Store::reset();
        }
        else
        {
            self::runJob($job);
            die();
        }
    }

    public static function mainLoop()
    {
        $bootstrap = Config::get('bootstrap');        
        if($bootstrap)
        {
            require Config::get("bootstrap");
        }
        
        Logger::info("Starting Ajumamoro");
        
        set_error_handler(
            function($no, $message, $file, $line){
                Logger::error("Job #" . self::$jobId . " Warning $message on line $line of $file");
            },
            E_WARNING
        );

       // Get Store;
        $delay = Config::get('delay', 200);

        do
        {
            $job = self::getNextJob();

            if($job !== false)
            {
                self::executeJob($job);
            }
            else
            {
                usleep($delay);
            }
        }
        while(true);
    }
    
    public static function getNextJob()
    {
        $broker = Broker::getInstance();
        $jobInfo = $broker->get();
        
        if(file_exists($jobInfo['path'])) require_once $jobInfo['path'];
        
        if(!class_exists($jobInfo['class']))
        {
            Logger::error("Class {$jobInfo['class']} for scheduled job not found");
            return false;
        }
       
        $job = unserialize($jobInfo['object']);
        
        if(is_object($job) && is_a($job, '\ajumamoro\Job'))
        {
            $job->setId($jobInfo['id']);
            return $job;
        }
        else
        {
            Logger::error("Scheduled job is not of type \\ajumamoro\\Job.");
            return false;
        }
    }     
}
