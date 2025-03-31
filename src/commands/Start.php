<?php

namespace ajumamoro\commands;

use ntentan\config\Config;
use Psr\Log\LoggerInterface;
use ajumamoro\BrokerInterface;
use ajumamoro\Runner;

class Start implements Command
{

    private LoggerInterface $logger;
    private Runner $runner;

    public function __construct(LoggerInterface $logger, Runner $runner)
    {
        $this->logger = $logger;
        $this->runner = $runner;
    }

    private function checkExistingInstance(): bool
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

    private function startDaemon($options): int
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

    public function run($options = []): void
    {
        
        if (isset($options['daemon'])) {
            echo("Starting ajumamoro daemon ... ");

            if ($this->checkExistingInstance() === false) {
                $pid = $this->startDaemon($options);
                echo($pid > 0 ? "OK [PID:$pid]\n" : "Failed\n");
            } else {
                echo("Failed\nAn instance of a ajumamoro is already running. You can supply a different PID file path if you want to run multiple instances.\n");
            }
        } else {
            try {
                $this->runner->mainLoop();
            } catch (\Exception $e) {
                $classname = get_class($e);
                $this->logger->critical("Failed to start runner. An exception of [{$classname}] was thrown {$e->getMessage()} on line {$e->getLine()} of {$e->getFile()}");
                $this->logger->debug($e->getTraceAsString());
            }
        }
    }
}
