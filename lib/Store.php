<?php
abstract class Store
{
    abstract public function put($job, $queue);
    abstract public function get();
    abstract public function init();
}
