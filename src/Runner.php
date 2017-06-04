<?php

namespace ajumamoro;

use Psr\Log\LoggerInterface;
use ntentan\config\Config;
use ajumamoro\BrokerInterface;

class Runner
{

    /**
     *
     * @var ajumamoro\Store
     */
    private $jobId;
    private $logger;
    private $config;
    private $broker;
    
    public function __construct(LoggerInterface $logger, BrokerInterface $broker, Config $config) {
        $this->logger = $logger;
        $this->broker = $broker;
        $this->config = $config;
    }

    /**
     *
     * @param \Exception $exception
     * @param string $prefix
     */
    public function logException(\Exception $exception, $prefix = "Exception thrown") {
        $class = new \ReflectionClass($exception);
        $this->logger->critical("$prefix [{$class->getName()}] {$exception->getMessage()} on line {$exception->getLine()} of {$exception->getFile()}\n");
        $this->logger->debug("Debug trace for exception {$exception->getTraceAsString()}");
    }

    public function deleteJob($job) {
        Store::getInstance()->delete($job);
    }

    private function runJob($job) {
        try {
            $this->logger->notice("Job #" . self::$jobId . " started.");
            $job->setup();
            $job->go();
            $job->tearDown();
            $this->logger->notice("Job #" . self::$jobId . " finished.");
        } catch (\Exception $e) {
            self::logException($e, "Job #" . self::$jobId . " Exception");
            $this->logger->alert("Job #" . self::$jobId . " died.");
        }
    }

    private function executeJob(Job $job) {
        self::$jobId = $job->getId();
        $this->logger->notice("Recived a new job #" . self::$jobId);
        //Store::getInstance()->markStarted(self::$jobId);

        if (!function_exists('pcntl_fork')) {
            self::runJob($job);
            $this->logger->notice("Job #" . self::$jobId . " exited.");
            return;
        }

        $pid = pcntl_fork();
        if ($pid) {
            pcntl_wait($status);
            $this->logger->notice("Job #" . self::$jobId . " exited.");
            //Store::getInstance()->markFinished(self::$jobId);
            //Store::reset();
        } else {
            self::runJob($job);
            die();
        }
    }

    public function mainLoop() {
        $bootstrap = $this->config->get('bootstrap');
        if ($bootstrap) {
            require $this->config->get('bootstrap');
        }

        $this->logger->info("Starting Ajumamoro");

        set_error_handler(
                function($no, $message, $file, $line) {
            $this->logger->error("Job #" . self::$jobId . " Warning $message on line $line of $file");
        }, E_WARNING
        );

        // Get Store;
        $delay = $this->config->get('delay', 200);

        do {
            $job = self::getNextJob();

            if ($job !== false) {
                self::executeJob($job);
            } else {
                usleep($delay);
            }
        } while (true);
    }

    public function getNextJob() {
        $jobInfo = $this->broker->get();

        if (file_exists($jobInfo['path']))
            require_once $jobInfo['path'];

        if (!class_exists($jobInfo['class'])) {
            $this->logger->error("Class {$jobInfo['class']} for scheduled job not found");
            return false;
        }

        $job = unserialize($jobInfo['object']);

        if (is_object($job) && is_a($job, '\ajumamoro\Job')) {
            $job->setId($jobInfo['id']);
            return $job;
        } else {
            $this->logger->error("Scheduled job is not of type \\ajumamoro\\Job.");
            return false;
        }
    }

}
