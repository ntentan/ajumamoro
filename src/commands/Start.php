<?php

namespace ajumamoro\commands;

use ntentan\config\Config;
use clearice\ClearIce;
use Psr\Log\LoggerInterface;
use ajumamoro\BrokerInterface;
use ajumamoro\Runner;

class Start
{

    private $logger;
    private $runner;
    private $config;
    private $broker;

    public function __construct(LoggerInterface $logger, BrokerInterface $broker, Runner $runner)
    {
        $this->logger = $logger;
        $this->broker = $broker;
        $this->runner = $runner;
    }

    private function checkExistingInstance()
    {
        $pidFile = Config::get('ajumamoro:pid_file', './.ajumamoro.pid');
        if (file_exists($pidFile) && is_readable($pidFile)) {
            $oldPid = file_get_contents($pidFile);
            if (posix_getpgid($oldPid) === false) {
                return false;
            } else {
                $this->logger->error("An already running ajumamoro process with pid $oldPid detected.\n");
                return true;
            }
        } else if (file_exists($pidFile)) {
            $this->logger->error("Could not read pid file [$pidFile].");
            return true;
        } else if (is_writable(dirname($pidFile))) {
            return false;
        } else {
            return false;
        }
    }

    private function startDaemon($options)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->logger->error("Sorry! could not start daemon.\n");
        } else if ($pid) {
            $this->logger->info("Daemon started with pid $pid.\n");
            $pidFile = Config::get('ajumamoro:pid_file', './ajumamoro.pid');
            file_put_contents($pidFile, $pid);
        } else {
            $this->runner->mainLoop($options);
        }
        return $pid;
    }

    public function run($options = [])
    {
        
        if (isset($options['daemon'])) {
            ClearIce::output("Starting ajumamoro daemon ... ");

            if ($this->checkExistingInstance() === false) {
                $pid = $this->startDaemon($options);
                ClearIce::output($pid > 0 ? "OK [PID:$pid]\n" : "Failed\n");
            } else {
                ClearIce::output("Failed\nAn instance already exists.\n");
            }
        } else {
            $this->runner->mainLoop();
        }
    }

}
