<?php

namespace ajumamoro\commands;

class Stop implements Command
{

    public function run($options)
    {
        ClearIce::output("Stopping ajumamoro daemon ... ");
        $options = Configuration::init($options);
        $pidFile = Configuration::get('pid_file', './ajumamoro.pid');
        if (file_exists($pidFile) && is_readable($pidFile)) {
            $pid = file_get_contents($pidFile);
            if (posix_kill($pid, SIGTERM)) {
                unlink($pidFile);
                ClearIce::output("OK\n");
            } else {
                ClearIce::output("Failed\nCould not kill running instance.\n");
            }
        } else {
            ClearIce::output("Failed\nNo instances detected.\n");
        }
    }

}
