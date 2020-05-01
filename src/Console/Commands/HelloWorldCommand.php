<?php

namespace OffeneVergaben\Console\Commands;

use Illuminate\Support\Str;
use OffeneVergaben\Console\Scraper;
use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class HelloWorldCommand
 * @package OffeneVergaben\Console\Commands
 *
 * Bare-bones command example
 */
class HelloWorldCommand extends AbstractCommand
{
    protected function configure() {
        $this->setName('helloworld');
        $this->setDescription('Print Hello World to the console.');
    }

    protected function exe() {
        $this->info('Hello World!');
    }

}