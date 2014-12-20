<?php
namespace ajumamoro;

use ntentan\logger\Logger;

class Ajumamoro
{
    /**
     *
     * @var ajumamoro\Store
     */
    private static $store = false;
    private static $params = false;
    private static $jobId;
    
    public static function init($params)
    {
        self::$params = $params;
    }
    
    /**
     * 
     * @return ajumamoro\Store
     */
    public static function getStore()
    {
        if(self::$store === false)
        {
            self::$store = Store::factory(self::$params);
            self::$store->init();
        }
        return self::$store;
    }
    
    public static function resetStore()
    {
        self::$store = false;
    }
    
    public static function getNextJob()
    {
        $jobInfo = self::getStore()->get();
        if(is_array($jobInfo))
        {
            require_once $jobInfo['class_file_path'];
            $job = unserialize($jobInfo['object']);
            if(is_a($job, "\\ajumamoro\\Ajuma"))
            {
                $job->setStore(self::getStore());
                $job->setId($jobInfo['id']);
                return $job;
            }
            else
            {
                Logger::error("Failed to execute job");
                self::getStore()->setStatus($jobInfo['id'], 'FAILED');
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    
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
        self::getStore()->delete($job);
    }
    
    public static function add(Ajuma $job)
    {
        $store = self::getStore();
        $jobClass = new \ReflectionObject($job);
        $store->put(serialize($job), $jobClass->getFileName(), $job->getTag());
        return $store->lastJobId();
    }
    
    public static function mainLoop($options)
    {
        Logger::info("Starting Ajumamoro");
        
        set_error_handler(
            function($no, $message, $file, $line){
                Logger::error("Job #" . self::$jobId . " Warning $message on line $line of $file");
            },
            E_WARNING
        );
        
       // Get Store;
        self::init($options);

        do
        {
            $job = self::getNextJob();

            if($job !== false)
            {
                self::$jobId = $job->getId();
                Logger::notice("Recived a new job #" . self::$jobId);
                self::getStore()->markStarted(self::$jobId);
                $pid = pcntl_fork();
                if($pid)
                {
                    pcntl_wait($status);
                    self::resetStore();
                    self::getStore()->markFinished(self::$jobId);
                }
                else
                {
                    try{
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
                    die();
                }
            }
            else
            {
                usleep(200);
            }
        }
        while(true);        
    }
}

