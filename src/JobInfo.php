<?php
namespace ajumamoro;

class JobInfo
{
    public readonly string $class;
    public readonly string $serialized;
    public string $id;

    public function __construct(Job $job)
    {
        $jobClass = new \ReflectionClass($job);
        $this->class = $jobClass->getName();
        $this->serialized = serialize($job);
    }
    
    public function __serialize(): array
    {
        return ['object' => $this->serialized, 'class_name' => $this->class, 'id' => $this->id];
    }
    
    public function __unserialize(array $serialized): void
    {
        $this->serialized = $serialized['object'];
        $this->class = $serialized['class_name'];
        $this->id = $serialized['id'];
    }
}