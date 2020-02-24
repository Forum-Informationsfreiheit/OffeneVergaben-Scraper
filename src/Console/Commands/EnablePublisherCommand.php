<?php

namespace OffeneVergaben\Console\Commands;

use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EnablePublisherCommand
 * @package OffeneVergaben\Console\Commands
 *
 */
class EnablePublisherCommand extends DisablePublisherCommand
{
    protected $disable = false;

    protected function configure() {
        $this->setName('enable:publisher');
        $this->setDescription('Enable a publisher, KDQs of enabled publishers will be scraped during scrape:kerndaten');
        $this->addArgument('publisher', InputArgument::REQUIRED,"Alias or reference_id of publisher");
    }
}