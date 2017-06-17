<?php

namespace ajumamoro;

use Psr\Log\LoggerInterface;
use ntentan\config\Config;
use ajumamoro\BrokerInterface;
use ntentan\panie\Container;

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
    private $container;
    
    public function __construct(Container $container, LoggerInterface $logger, BrokerInterface $broker, Config $config) {
        $this->logger = $logger;
        $this->broker = $broker;
        $this->config = $config;
        $this->container = $container;
    }

    /**
     *
     * @param \Exception $exception
     * @param string $prefix
     */
    public function logException(\Exception $exception, $prefix = "Exception thrown") {
        $class = new \ReflectionClass($exception);
        $this->logger->critical("$prefix [{$class->getName()}] {$exception->getMessage()} on line {$exception->getLine()} of {$exception->getFile()}\n");
        $this->logger->debug("Debug trace for exception: {$exception->getTraceAsString()}");
    }

    public function deleteJob($job) {
        Store::getInstance()->delete($job);
    }

    /**
     * 
     * @param \ajumamoro\Job $job
     */
    private function runJob($job) {
        try {
            $this->logger->notice("Job #{$this->currentJobId} [{$job->getName()}] started.");
            $status = $this->broker->getStatus($job->getId());
            $status['status'] = Job::STATUS_RUNNING;
            $status['started'] = date(DATE_RFC3339_EXTENDED);
            $this->broker->setStatus($job->getId(), $status);
            $job->setup();
            $response = $job->go();
            $job->tearDown();
            $status['status'] = Job::STATUS_FINISHED;
            $status['finished'] = date(DATE_RFC3339_EXTENDED);
            $status['response'] = $response;
            $this->broker->setStatus($job->getId(), $status);
            $this->logger->notice("Job #{$this->currentJobId} [{$job->getName()}] finished.");
        } catch (\Exception $e) {
            $status['status'] = Job::STATUS_FAILED;
            $status['failed'] = date(DATE_RFC3339_EXTENDED);
            $this->broker->setStatus($job->getId(), $status);
            $this->logException($e, "Job #{$this->currentJobId}  [{$job->getName()}] Exception");
            $this->logger->alert("Job #{$this->currentJobId}  [{$job->getName()}] died.");
        }
    }

    private function executeJob(Job $job) {
        $this->currentJobId = $job->getId();
        $job->setLogger($this->logger);
        $job->setContainer($this->container);
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
        if ($bootstrap) {
            $container = $this->container;
            (function() use ($container) {
                require $this->config->get('bootstrap');
            })();
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

        if (!class_exists($jobInfo['class'])) {
            $this->logger->error("Class {$jobInfo['class']} for scheduled job not found");
            return false;
        }

        $job = unserialize($jobInfo['object']);

        if (is_object($job) && is_a($job, '\ajumamoro\Job')) {
            $job->setId($jobInfo['id']);
            return $job;
        } else {
            $this->logger->error("Scheduled job is not of type \\ajumamoro\\Job");
            return false;
        }
    }

}
