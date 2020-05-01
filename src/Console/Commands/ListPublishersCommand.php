<?php

namespace OffeneVergaben\Console\Commands;

use Illuminate\Support\Carbon;
use OffeneVergaben\Console\Exceptions\ScraperException;
use OffeneVergaben\Console\Scraper;
use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListPublishersCommand
 * @package OffeneVergaben\Console\Commands
 *
 */
class ListPublishersCommand extends AbstractCommand
{
    protected $scraper;

    const OPTION_CHECK = 'check';

    protected function configure() {
        $this->setName('list:publishers');
        $this->setDescription('List all known publishers');
        $this->addOption('check','c',InputOption::VALUE_NONE,'Optionally perform a check for each publisher to test if the source url is available and responds with a valid xml. Takes a while.');
    }

    protected function init() {
        $this->db = app()->getDatabase();
        $this->db->connect();

        $this->scraper = new Scraper(null,['interval' => 200]);
    }

    protected function exe() {
        if ($this->input->getOption(self::OPTION_CHECK)) {
            $this->info('<comment>Checking source availability, please be patient...</comment>');
            $this->output->writeln('');
        }

        $table = new Table($this->output);
        $table->setHeaders($this->getTableHeaders())
            ->setRows($this->getTableRows());

        $table->render();

        $this->output->writeln('');

        if (!$this->input->getOption(self::OPTION_CHECK)) {
            $this->info('<comment>To perform a data availability check for each publisher use this command with the </comment>--check</comment> <comment>option</comment>');
            $this->output->writeln('');
        }

        $this->info('<comment>To </comment>enable<comment> a publisher use</comment>');
        $this->info('bin/console enable:publisher <reference_id>');
        $this->output->writeln('');
        $this->info('<comment>To disable a publisher use</comment>');
        $this->info('bin/console disable:publisher <reference_id>');
    }

    protected function getTableRows() {
        $publishers = $this->getPublishers();

        $rows = [];
        $i = 0;

        foreach($publishers as $p) {
            $row = [
                ++$i,
                text_shorten($p['name'],30),
                $p['reference_id'],
                Carbon::createFromTimeString($p['created_at'])->format('d.m.Y'),
                $p['active'] ? 'enabled' : '<comment>disabled</comment>'
            ];

            if ($this->input->getOption('check')) {
                $row[] = $this->checkQuelle($p['url']) ? '<info>OK</info>' : '<error>FAILED</error>';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array
     */
    protected function getTableHeaders() {
        $headers = [
            '#',
            'name',
            'data.gv.at reference_id',
            'added',
            'status'
        ];

        if ($this->input->getOption('check')) {
            $headers[] = 'check';
        }

        return $headers;
    }

    protected function checkQuelle($url) {
        try {
            $this->scraper->getXml($url);
        } catch(ScraperException $ex) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    protected function getPublishers() {
        return $this->db->get(
            "SELECT * FROM quellen ORDER BY active desc, name asc");
    }
}