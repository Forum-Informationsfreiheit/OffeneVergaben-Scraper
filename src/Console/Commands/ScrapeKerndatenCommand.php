<?php

namespace OffeneVergaben\Console\Commands;

use OffeneVergaben\Console\Commands\AbstractCommand as Command;
use Carbon\Carbon;
use OffeneVergaben\Console\Database;
use OffeneVergaben\Console\Exceptions\ScraperException;
use OffeneVergaben\Console\Scraper;
use OffeneVergaben\Models\DOM\Kdq;
use OffeneVergaben\Models\DOM\KdqItem;
use Symfony\Component\Console\Input\InputArgument;

class ScrapeKerndatenCommand extends Command
{
    /**
     * @var Database\MysqlConnection $db
     */
    protected $db;

    /**
     * @var Scraper $scraper
     */
    protected $scraper;

    /**
     *
     */
    protected function configure() {
        $this->setName('scrape:kerndaten');
        $this->setDescription('Collect kerndaten from known publishers.');
        $this->addArgument('publisher', InputArgument::OPTIONAL,"Alias or reference_id of publisher");
    }

    /**
     *
     */
    protected function init() {
        $this->db = $this->app->getDatabase();
        $this->db->connect();

        $this->scraper = new Scraper();

        $this->selfHandleInterrupts();
    }

    /**
     * Execute the command
     */
    protected function exe() {
        $quellen = $this->getQuellen($this->input->getArgument('publisher'));

        foreach($quellen as $quelle) {
            $this->checkForInterrupts();

            $this->info('<comment>Checking '.$quelle['name'].'</comment>');

            $kdqXmlString = $this->fetch($quelle['url']);
            if (!$kdqXmlString) {
                // skip kdq on error
                continue;
            }

            $kdq = new Kdq($quelle['alias'], $kdqXmlString);
            $needsUpdate = $this->filterItems($kdq);

            $this->info('<comment>Found '. $needsUpdate['new'] . ' new and ' .$needsUpdate['updated']. ' updated items</comment>');

            foreach($needsUpdate['items'] as $needsUpdateItem) {
                $this->checkForInterrupts();

                $itemXmlString = $this->fetch($needsUpdateItem->geturl());
                if (!$itemXmlString) {
                    // skip kd on error
                    continue;
                }

                $needsUpdateItem->setData('xml', $itemXmlString);
                $this->insertKerndaten($quelle['alias'], $needsUpdateItem);
            }
        }
    }

    /**
     * Filters the items contained in the provided kdq.
     * Return items that are in need of an update and ignore the others.
     *
     * @param Kdq $kdq - kerndatenquelle holding all the freshly scraped kdq items
     *
     * @return array
     */
    protected function filterItems(Kdq $kdq) {

        $items = $kdq->getItems();
        $needsUpdate = [
            'items'   => [],
            'new'     => 0,
            'updated' => 0,
        ];

        // loop over each item in kdq and check it against the database
        foreach($items as $item) {
            $stored = $this->getLastKerndaten($kdq->getQuelle(), $item->getId());

            if (!$stored) {
                $item->setData('last_version',0);
                $needsUpdate['items'][] = $item;
                $needsUpdate['new']++;
                continue;
            }

            // compare timestamps of scraped item and (last) stored item
            // use milliseconds as base for comparison
            $storedLastMod = Carbon::createFromTimeString($stored['item_lastmod']);
            $storedLastModMs = $storedLastMod->setMilliseconds($storedLastMod->get('milliseconds'));
            $itemLastModMs = $item->getLastMod()->copy()->setMilliseconds($item->getLastMod()->get('milliseconds'));

            if ($itemLastModMs->greaterThan($storedLastModMs)) {
                // remember the last version number (or 0 if new)
                $item->setData('last_version',intval($stored['version']));
                $needsUpdate['items'][] = $item;
                $needsUpdate['updated']++;
            }
        }

        return $needsUpdate;
    }

    /**
     * Actually perform the request.
     *
     * @param $uri
     * @return null|String
     */
    protected function fetch($uri) {
        $this->info('GET ' . $uri);

        try {
            // try to get the xml
            $content = convert_to_utf8($this->scraper->getXml($uri));

            return $content;

        } catch(ScraperException $ex) {
            $this->error('Unable to fetch xml: '.$ex->getMessage());
        }

        return null;
    }

    /**
     * @param String $quelle
     * @param KdqItem $item
     */
    protected function insertKerndaten($quelle, KdqItem $item) {
        $data = [
            'quelle' => $quelle,
            'version' => $item->getData('last_version') + 1,
            'item_id' => $item->getId(),
            'item_url' => $item->getUrl(),
            'item_lastmod' => $item->getLastMod()->toDateTimeString('microsecond'),
            'xml' => $item->getData('xml'),
            'created_at' => Carbon::now()->toDateTimeString(),
        ];

        $this->db->insert(
            "INSERT INTO kerndaten (". join(',',array_keys($data)) .") VALUES (". str_repeat('?,', count($data) - 1) . '?' .")",
            array_values($data));
    }

    protected function getLastKerndaten($quellenAlias, $itemId) {
        $last = $this->db->get(
            "SELECT * FROM kerndaten WHERE quelle = ? AND item_id = ? ORDER BY VERSION DESC LIMIT 1",
            [$quellenAlias, $itemId]);

        return count($last) ? $last[0] : null;
    }

    /**
     * @return array
     */
    protected function getQuellen($publisher) {

        if (!$publisher) {
            return $this->db->get(
                "SELECT * FROM quellen WHERE active = 1");
        } else {
            return $this->db->get(
                "SELECT * FROM quellen WHERE reference_id = ? OR alias = ?",
                [$publisher, $publisher]
            );
        }


    }
}