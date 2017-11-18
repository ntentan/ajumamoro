<?php

namespace ajumamoro;

use Psr\Log\LoggerInterface;
use ntentan\config\Config;
use ajumamoro\BrokerInterface;

/**
 * Runs the main loop of ajumamoro
 */
class Runner
{
    /**
     * The id of the current job being executed.
     *
     * @var int
     */
    private $currentJobId;

    /**
     * An instance of a logger interface used for logging job output.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Holds the configuration for this ajumamoro instance.
     *
     * @var Config
     */
    private $config;

    /**
     * An instance of the broker from which jobs are received.
     *
     * @var BrokerInterface
     */
    private $broker;
    
    /**
     * Create a new Runner instance
     *
     * @param LoggerInterface $logger A logger to log all runner events and job output to.
     * @param BrokerInterface $broker A broker to use in receiving and distributing jobs.
     * @param Config $config Configurations for the broker and ajumamoro as a whole.
     */
    public function __construct(LoggerInterface $logger, BrokerInterface $broker, Config $config)
    {
        $this->logger = $logger;
        $this->broker = $broker;
        $this->config = $config;
    }

    /**
     * Logs any exceptions that are thrown by running jobs.
     *
     * @param \Exception $exception
     * @param string $prefix
     */
    public function logException(\Exception $exception, $prefix = "Exception thrown")
    {
        $class = new \ReflectionClass($exception);
        $this->logger->critical("$prefix [{$class->getName()}] {$exception->getMessage()} on line {$exception->getLine()} of {$exception->getFile()}\n");
        $this->logger->debug("Debug trace for exception: {$exception->getTraceAsString()}");
    }

    /**
     * Actually run a job and log any exceptions or status messages.
     *
     * @param \ajumamoro\Job $job
     */
    private function runJob($job)
    {
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

    /**
     * Fork off this process and wait while job is executed.
     *
     * @param Job $job
     * @return void
     */
    private function executeJob(Job $job)
    {
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

    /**
     * The runners main loop that waits on the broker for jobs.
     *
     * @return void
     */
    public function mainLoop()
    {
        $bootstrap = $this->config->get('broker');
        if ($bootstrap) {
            (function () {
                require $this->config->get('bootstrap');
            })();
        }
        $this->logger->info("Ajumamoro");
        set_error_handler(
            function ($no, $message, $file, $line) {
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

    /**
     * Get the next job from the broker if any exists.
     * Ths function actuall polls the broker for any pending jobs to be executed.
     *
     * @return bool|Job
     */
    public function getNextJob()
    {
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
