<?php

namespace OffeneVergaben\Console\Commands;

use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DisablePublisherCommand
 * @package OffeneVergaben\Console\Commands
 *
 */
class DisablePublisherCommand extends AbstractCommand
{
    protected $disable = true;

    protected function configure() {
        $this->setName('disable:publisher');
        $this->setDescription('Disable a publisher, KDQs of disabled publishers will be skipped during scrape:kerndaten');
        $this->addArgument('publisher', InputArgument::REQUIRED,"Alias or reference_id of publisher");
    }

    protected function init() {
        $this->db = app()->getDatabase();
        $this->db->connect();
    }

    protected function exe() {
        $publisherInp = $this->input->getArgument('publisher');

        $publisher = $this->findPublisherByAlias($publisherInp);
        $publisher = $publisher ? $publisher : $this->findPublisherByReferenceId($publisherInp);

        if (!$publisher) {
            $this->error("Publisher $publisherInp does not exist");
            return;
        }

        $success = $this->setPublisherActive($publisher, !$this->disable);

        if ($success) {
            $this->info('<comment>Publisher '.$publisher['name'].' '.($this->disable ? ' <error>disabled</error>' : '<info>enabled</info>').'</comment>');
        } else {
            $this->error('No change.');
        }
    }

    protected function setPublisherActive($publisher, $active) {
        $active = $active ? 1 : 0;

        return $this->db->update("UPDATE quellen SET active = ? WHERE reference_id = ?",[$active, $publisher['reference_id']]);
    }

    protected function findPublisherByAlias($alias) {
        $result = $this->db->get(
            "SELECT * FROM quellen WHERE alias= ? LIMIT 1",
            [$alias]);

        return count($result) ? $result[0] : null;
    }

    protected function findPublisherByReferenceId($id) {
        $result = $this->db->get(
            "SELECT * FROM quellen WHERE reference_id= ? LIMIT 1",
            [$id]);

        return count($result) ? $result[0] : null;
    }
}