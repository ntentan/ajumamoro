<?php

namespace ajumamoro;

use ntentan\panie\Container;
use ntentan\utils\Text;

class Queue
{
    private BrokerInterface $broker;

    /**
     * Queue constructor.
     * @param BrokerInterface $broker An istance of the broker from which jobs are retrieved
     */
    public function __construct(BrokerInterface $broker)
    {
        $this->broker = $broker;
    }

    /**
     * Add a job to the job queue.
     *
     * @param Job $job
     * @return string
     */
    public function add(Job $job): string
    {
        $jobId = $this->broker->put(new JobInfo($job)); //['object' => $object, 'class' => $name]);
        $this->broker->setStatus($jobId,
            ['status' => Job::STATUS_QUEUED, 'queued'=> date(DATE_RFC3339_EXTENDED)]
        );
        return $jobId;
    }

    /**
     * Retrieves the status of a job on the queue.
     */
    public function getJobStatus($jobId): array
    {
        return $this->broker->getStatus($jobId);
    }

}
