<?php

namespace OffeneVergaben\Console\Commands;

use Illuminate\Support\Carbon;
use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Meta command for running scrape:publishers and scrape:kerndaten
 *
 * Class DisablePublisherCommand
 * @package OffeneVergaben\Console\Commands
 *
 */
class ScrapeAllCommand extends AbstractCommand
{
    protected function configure() {
        $this->setName('scrape:all');
        $this->setDescription('Executes scrape:publishers and scrape:kerndaten in consecutive order.');
    }

    protected function exe() {
        $returnCode = $this->runScrapePublishers();

        if ($returnCode !== 0) {
            $this->error("Scrape publishers command returned an error. Exit early. Returncode " . $returnCode);
            return;
        }

        $returnCode = $this->runScrapeKerndaten();

        if ($returnCode !== 0) {
            $this->error("Scrape Kerndaten returned an error. Returncode " . $returnCode);
            return;
        }
    }

    protected function runScrapePublishers() {
        $command = $this->getApplication()->find('scrape:publishers');

        $arguments = [
            'command' => 'scrape:publishers',
        ];

        return $command->run(new ArrayInput($arguments), $this->output);
    }

    protected function runScrapeKerndaten() {
        $command = $this->getApplication()->find('scrape:kerndaten');

        $arguments = [
            'command' => 'scrape:kerndaten',
        ];

        return $command->run(new ArrayInput($arguments), $this->output);
    }
}