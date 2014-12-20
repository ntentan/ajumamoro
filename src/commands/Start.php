<?php

namespace ajumamoro\commands;

use ntentan\logger\Logger;
use ajumamoro\Ajumamoro;
use ajumamoro\Configuration;

class Start implements \clearice\Command
{
    public function run($options)
    {
        $options = Configuration::init($options);
        if($options['daemon'] === true)
        {
            Logger::init('ajumamoro.log', 'ajumamoro');
            $pid = pcntl_fork();
            if($pid == -1)
            {
                Logger::error("Could not start daemon.");
            }
            else if($pid)
            {
                return;
            }
            else
            {
                Ajumamoro::mainLoop($options);
            }
        }
        else
        {
            Logger::init('php://output', 'ajumamoro');
            Ajumamoro::mainLoop($options);
        }        
    }
}
