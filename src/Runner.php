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
    private $currentJobId;
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
            $this->logger->notice("Job #{$this->currentJobId} [{$job->getName()}] started.");
            $job->setup();
            $job->go();
            $job->tearDown();
            $this->logger->notice("Job #{$this->currentJobId} [{$job->getName()}] finished.");
        } catch (\Exception $e) {
            $this->logException($e, "Job #{$this->currentJobId}  [{$job->getName()}] Exception");
            $this->logger->alert("Job #{$this->currentJobId}  [{$job->getName()}] died.");
        }
    }

    private function executeJob(Job $job) {
        $this->currentJobId = $job->getId();
        $job->setLogger($this->logger);
        $this->logger->notice("Starting job #" . $this->currentJobId);

        if (!function_exists('pcntl_fork')) {
            $this->runJob($job);
            return;
        }

        $pid = pcntl_fork();
        if ($pid) {
            pcntl_wait($status);
        } else {
            $this->runJob($job);
            die();
        }
    }

    public function mainLoop() {
        $bootstrap = $this->config->get('broker');
        var_dump($bootstrap);
        if ($bootstrap) {
            require $this->config->get('bootstrap');
        }
        $this->logger->info("Ajumamoro");
        set_error_handler(
            function($no, $message, $file, $line) {
                $this->logger->error(
                    "Job #{$this->currentJobId} Error: $message on line $line of $file"
                );
            }, 
            E_WARNING
        );

        // Get Store;
        $delay = $this->config->get('delay', 200);

        do {
            $job = $this->getNextJob();

            if ($job !== false) {
                $this->executeJob($job);
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
