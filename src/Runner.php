<?php
namespace ajumamoro;

use ntentan\logger\Logger;

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
        Store::getInstance()->markStarted(self::$jobId);

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
            Store::getInstance()->markFinished(self::$jobId);
            Store::reset();
        }
        else
        {
            self::runJob($job);
            die();
        }
    }

    public static function mainLoop()
    {
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
                usleep(200);
            }
        }
        while(true);
    }
    
    public static function getNextJob()
    {
        $store = Store::getInstance();
        $jobInfo = $store->get();
        if(is_array($jobInfo))
        {
            require_once $jobInfo['class_file_path'];
            $job = unserialize($jobInfo['object']);
            if(is_a($job, "\\ajumamoro\\Job"))
            {
                $job->setStore($store);
                $job->setId($jobInfo['id']);
                return $job;
            }
            else
            {
                Logger::error("Scheduled job is not of type \\ajumamoro\\Job.");
                $store->setStatus($jobInfo['id'], 'FAILED');
                return false;
            }
        }
        else
        {
            return false;
        }
    }     
}
