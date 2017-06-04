<?php

namespace ajumamoro\commands;

use ajumamoro\Runner;
use ntentan\config\Config;
use ntentan\panie\Container;
use clearice\ClearIce;
use Psr\Log\LoggerInterface;
use ntentan\utils\Text;
use ajumamoro\BrokerInterface;


class Start implements \clearice\CommandInterface
{
    private $logger;
    private $runner;
    private $container;
    private $config;
    
    public function __construct(Container $container, LoggerInterface $logger, Config $config) {
        $this->logger = $logger;
        $this->container = $container;
        $this->config = $config;
    }
    
    public static function getCommandOptions() {
        return [
            'command' => 'start',
            'help' => 'start ajumamoro',
            'options' => [ 
                array(
                    'short' => 'b',
                    'long' => 'broker',
                    'help' => 'specify the backend to be used for storing job tasks. Supported backends: redis, postgresql, mysql and sqlite',
                    'has_value' => true,
                    'value' => 'STORE',
                    'command' => 'start'
                ),
                array(
                    'short' => 's',
                    'long' => 'bootstrap',
                    'help' => 'path to a script to include when ajumamoro starts',
                    'has_value' => true,
                    'value' => 'PATH',
                    'command' => 'start' 
                ),
                array(
                    'short' => 'l',
                    'long' => 'load-class-files',
                    'help' => 'forces ajumamoro to load php files of the job classes. By default ajumamoro depends on the autoloader to handle the loading of class files.',
                    'command' => 'start'
                ),
                array(
                    'short' => 'd',
                    'long' => 'daemon',
                    'help' => 'run the process as a daemon',
                    'command' => 'start'
                ),
                array(
                    'short' => 'D',
                    'long' => 'delay',
                    'help' => 'waiting time in microseconds between broker polling',
                    'command' => 'start'
                ),
                array(
                    'short' => 'c',
                    'long' => 'config',
                    'help' => 'a path to the configuration file for ajumamoro',
                    'value' => 'PATH',
                    'has_value' => true,
                    'command' => 'start'
                )
            ]
        ];
    }
    
    private function checkExistingInstance() {
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

    private function startDaemon($options) {
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

    public function run($options = []) {
        if (isset($options['config'])) {
            $this->config->readPath($options['config']);
            $options['broker'] = $this->config->get('broker');
        }
        $brokerClass = sprintf(
            "\\ajumamoro\\brokers\\%sBroker", 
            Text::ucamelize($options['broker'])
        );
        $this->container->bind(BrokerInterface::class)->to($brokerClass);
        $this->runner = $this->container->resolve(Runner::class);
        
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
