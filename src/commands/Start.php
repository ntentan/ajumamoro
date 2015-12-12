<?php

namespace ajumamoro\commands;

use ntentan\logger\Logger;
use ajumamoro\Runner;
use ajumamoro\Configuration;
use clearice\ClearIce;

class Start implements \clearice\Command
{
    private function checkExistingInstance()
    {
        $pidFile = Configuration::get('pid_file', './.ajumamoro.pid');
        if(file_exists($pidFile) && is_readable($pidFile))
        {
            $oldPid = file_get_contents($pidFile);
            if(posix_getpgid($oldPid) === false)
            {
                return false;
            }
            else
            {
                Logger::error("An already running ajumamoro process with pid $oldPid detected.\n");
                return true;
            }
        }
        else if(file_exists($pidFile))
        {
            Logger::error("Could not read pid file [$pidFile].");
            return true;
        }
        else if(is_writable(dirname($pidFile)))
        {
            return false;
        }
        else
        {
            return false;
        }
    }
    
    private function startDaemon($options)
    {
        $pid = pcntl_fork();
        if($pid == -1)
        {
            Logger::error("Sorry! could not start daemon.\n");
        }
        else if($pid)
        {
            Logger::info("Daemon started with pid $pid.\n");
            $pidFile = Configuration::get('pid_file', './ajumamoro.pid');
            file_put_contents($pidFile, $pid);
        }
        else
        {
            Runner::mainLoop($options);
        }    
        return $pid;
    }
    
    public function run($options)
    {
        $options = Configuration::init($options);
        if($options['daemon'] === true)
        {
            ClearIce::output("Starting ajumamoro daemon ... ");
            Logger::init(Configuration::get('log_file', './ajumamoro.log'), 'ajumamoro');
            
            if($this->checkExistingInstance() === false)
            {
                $pid = $this->startDaemon($options);
                ClearIce::output($pid > 0 ? "OK [PID:$pid]\n" : "Failed\n");
            }
            else
            {
                ClearIce::output("Failed\nAn instance already exists.\n");
            }
        }
        else
        {
            Logger::init('php://output', 'ajumamoro');
            Runner::mainLoop($options);
        }        
    }
}
