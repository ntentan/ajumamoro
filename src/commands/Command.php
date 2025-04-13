<?php

namespace ajumamoro\commands;

interface Command
{
    public function run(): int;
}