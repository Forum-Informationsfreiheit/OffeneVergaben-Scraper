<?php

namespace OffeneVergaben\Console\Database;


interface ConnectionInterface
{
    public function connect();

    public function disconnect();
}