<?php
namespace ajumamoro;

use ajumamoro\exceptions\BrokerException;
use Predis\Connection\ConnectionException;
use Psr\Log\LoggerInterface;

/**
 * Runs the main loop of ajumamoro
 */
class Runner
{
    /**
     * The id of the current job being executed.
     */
    private int|null $currentJobId = null;

    /**
     * An instance of a logger interface used for logging job output.
     */
    private LoggerInterface $logger;

    /**
     * Holds the configuration for this ajumamoro instance.
     */
    private array $config;

    /**
     * An instance of the broker from which jobs are received.
     */
    private BrokerInterface $broker;
    
    /**
     * Create a new Runner instance.
     */
    public function __construct(LoggerInterface $logger, BrokerInterface $broker, array $ajumamoroConfig)
    {
        $this->logger = $logger;
        $this->broker = $broker;
        $this->config = $ajumamoroConfig;
    }

    /**
     * Logs any exceptions that are thrown by running jobs.
     */
    private function logException(\Exception $exception, string $prefix = "Exception thrown"): void
    {
        $class = new \ReflectionClass($exception);
        $this->logger->critical("$prefix [{$class->getName()}] {$exception->getMessage()} on line {$exception->getLine()} of {$exception->getFile()}\n");
        $this->logger->debug("Debug trace for exception: {$exception->getTraceAsString()}");
    }

    /**
     * Actually run a job and log any exceptions or status messages.
     */
    private function runJob(Job $job): void
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
     */
    private function executeJob(Job $job): void
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
     */
    public function mainLoop(): void
    {
        $bootstrapScript = $this->config['arguments']['bootstrap'];
        if (isset($bootstrapScript) && file_exists($bootstrapScript)) {
            (function () use ($bootstrapScript) {
                require $bootstrapScript;
            })();
        }
        $this->logger->info("Ajumamoro started.");

        // Get Store;
        $delay = $this->config['delay'] ?? 200;

        do {
            $this->logger->info("Waiting for the next job.");
            try {
                $job = $this->getNextJob();
                if ($job !== false) {
                    $this->executeJob($job);
                } else {
                    $this->logger->info("Delaying for {$delay} milliseconds.");
                    usleep($delay);
                }
            } catch (BrokerException $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->logException($e);
            }
        } while (true);
    }

    /**
     * Get the next job from the broker if any exists.
     * Ths function actually polls the broker for any pending jobs to be executed.
     *
     * @return bool|Job
     */
    public function getNextJob(): bool|Job
    {
        $jobInfo = $this->broker->get();

        if (!class_exists($jobInfo->class)) {
            $this->logger->error("Class {$jobInfo['class']} for scheduled job not found");
            return false;
        }

        $job = unserialize($jobInfo->serialized);

        if (is_object($job) && is_a($job, '\ajumamoro\Job')) {
            $job->setId($jobInfo->id);
            return $job;
        } else {
            $this->logger->error("Scheduled job is not of type \\ajumamoro\\Job");
            return false;
        }
    }
}
