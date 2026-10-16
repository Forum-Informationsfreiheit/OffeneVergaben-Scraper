<?php

namespace OffeneVergaben\Console\Commands;

use Carbon\Carbon;
use Illuminate\Support\Str;
use OffeneVergaben\Console\Exceptions\ScraperException;
use OffeneVergaben\Console\DataGvAt;
use OffeneVergaben\Console\Scraper;
use OffeneVergaben\Console\Database;

/**
 * Get the publishers list from data.gv.at and refresh the database table.
 *
 * @package OffeneVergaben\Console\Commands
 */
class GetPublishersCommand extends AbstractCommand
{
    /**
     * @var Database\MysqlConnection $db
     */
    protected $db;

    /**
     * @var Scraper $scraper
     */
    protected $scraper;

    protected function configure() {
        $this->setName('scrape:publishers');
        $this->setDescription('Refresh the list of publishers (source: data.gv.at)');
    }

    protected function init() {
        $this->db = $this->app->getDatabase();
        $this->db->connect();

        $this->selfHandleInterrupts();

        $this->scraper = new Scraper();
    }

    protected function exe() {
        // make use of the data.gv.at search api, fetch datasets by keyword
        // @see https://www.data.gv.at/api/hub/search/
        // sort order is actually irrelevant as every single dataset needs to be checked anyway
        $keyword = "ausschreibung";

        try {
            $packages = (new DataGvAt($this->scraper))->getPackagesByKeyword($keyword, function($url) {
                $this->checkForInterrupts();
                $this->info('GET ' . $url);
            });
        } catch(ScraperException $ex) {
            $this->error('data.gv.at API call not successful: '.$ex->getMessage());
            return;
        }

        $this->updatePublishers($packages);
    }

    /**
     * Update publishers
     *
     * @param array $packages
     */
    protected function updatePublishers($packages) {

        foreach($packages as $package) {
            $this->checkForInterrupts();

            $xmlResources = array_values(array_filter($package->resources,function($r) {
                return strtoupper($r->format) === 'XML';
            }));

            if (count($xmlResources) === 0) {
                continue;
            }

            // usually there will be 1 resource for 1 publisher
            // but the data structure allows for n resources per publisher, log that as an error
            if (count($xmlResources) > 1) {
                $this->error('Multiple XML resources found for publisher '.$package->publisher . ' proceeding with resource '.$xmlResources[0]->id . '. Other resources are ignored.');
            }

            // in any case continue with the first one
            $resource = $xmlResources[0];

            $existing = $this->findQuelleByReferenceId($package->id);

            if (!$existing) {
                $check = $this->checkQuelle($resource);

                $this->info('<comment>New publisher '.text_shorten($package->publisher).' | KDQ check '.($check ? '<info>OK</info>' : '<error>FAILED</error>').'</comment>');

                $this->insertQuelle($package, $resource, $check);
            } else {
                $this->updateQuelle($existing, $package, $resource);
            }
        }
    }

    protected function checkQuelle($resource) {
        try {
            $this->scraper->getXml($resource->url);
        } catch(ScraperException $ex) {
            return false;
        }

        return true;
    }

    protected function insertQuelle($package, $resource, $active) {

        $alias = substr(Str::slug($package->publisher, '', 'de'),0,20);

        // alias is a unique key, check against db for existence
        if ($this->findQuelleByAlias($alias)) {
            $alias = $this->getNewChainedAlias($alias);
        }

        $data = [
            'alias' => $alias,
            'reference_id' => $package->id,
            'active' => $active ? 1 : 0,
            'name' => trim($package->publisher),
            'url' => $resource->url,
            'created_at' => Carbon::now()->toDateTimeString(),
        ];

        $this->db->insert(
            "INSERT INTO quellen (". join(',',array_keys($data)) .") VALUES (". str_repeat('?,', count($data) - 1) . '?' .")",
            array_values($data));
    }

    protected function updateQuelle($quelle, $package, $resource) {
        // url up to date ? nothing to do
        if ($quelle['url'] == $resource->url) {
            return;
        }

        $check = $this->checkQuelle($resource);

        // could potentially also check for updates on other meta data... ?
        $this->info('<comment>Updating kdq url for '.$package->publisher.' | KDQ check '.($check ? '<info>OK</info>' : '<error>FAILED</error>').'</comment>');

        // update url
        $this->db->update("UPDATE quellen SET url = ? WHERE reference_id = ?",[ $resource->url, $quelle['reference_id'] ]);
    }

    /**
     * @return array
     */
    protected function findQuelleByReferenceId($id) {
        $result = $this->db->get(
            "SELECT * FROM quellen WHERE reference_id= ? LIMIT 1",
            [$id]);

        return count($result) ? $result[0] : null;
    }

    /**
     * @return array
     */
    protected function findQuelleByAlias($alias) {
        $result = $this->db->get(
            "SELECT * FROM quellen WHERE alias= ? LIMIT 1",
            [$alias]);

        return count($result) ? $result[0] : null;
    }

    /**
     * In case multiple publishers exist on data.gv.at with the same name
     * (or at least based on the first 20 relevant characters),
     * add a numeric suffix.
     *
     * @param $alias
     *
     * @return string
     */
    protected function getNewChainedAlias($alias) {
        $base = $alias;

        // Why 17? Need 17+18+19+20 for the numeric suffix e.g. '_001'
        // DB Column alias has a limit of 20 chars need to add 1 separation char and 3 chars for the number
        if (strlen($alias) >= 17) {
            $base = substr($alias, 0, 16);
        }

        $index = 1;
        $numericAlias = null;

        // this logic will fail if the numeric range of 1-999 is exceeded
        // ... but that should not happen
        while(true) {
            $suffix = str_pad((string)$index, 3, "0", STR_PAD_LEFT);
            $numericAlias = $base . '_' . $suffix;

            if ($this->findQuelleByAlias($numericAlias)) {
                $index++;
                continue;
            }

            // found one that doesn't exist yet, yey
            break;
        }

        return $numericAlias;
    }
}