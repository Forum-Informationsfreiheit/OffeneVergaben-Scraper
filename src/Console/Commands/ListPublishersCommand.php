<?php

namespace OffeneVergaben\Console\Commands;

use Illuminate\Support\Carbon;
use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListPublishersCommand
 * @package OffeneVergaben\Console\Commands
 *
 */
class ListPublishersCommand extends AbstractCommand
{
    protected $disable = true;

    protected function configure() {
        $this->setName('list:publishers');
        $this->setDescription('List all known publishers');
    }

    protected function init() {
        $this->db = app()->getDatabase();
        $this->db->connect();
    }

    protected function exe() {
        $publishers = $this->getPublishers();

        $i = 0;

        $table = new Table($this->output);
        $table
            ->setHeaders(['#','Reference_id', 'Name', 'Added', 'Status'])
            ->setRows(array_map(function($p) use (&$i) {
                return [
                    ++$i,
                    $p['reference_id'],
                    text_shorten($p['name'],30),
                    Carbon::createFromTimeString($p['created_at'])->format('d.m.Y'),
                    $p['active'] ? 'enabled' : '<comment>disabled</comment>'
                ];
            },$publishers));
        ;
        $table->render();
    }

    /**
     * @return array
     */
    protected function getPublishers() {
        return $this->db->get(
            "SELECT * FROM quellen ORDER BY active desc, name asc");
    }
}