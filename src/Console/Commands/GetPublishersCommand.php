<?php

namespace OffeneVergaben\Console\Commands;

use Carbon\Carbon;
use Illuminate\Support\Str;
use OffeneVergaben\Console\Exceptions\ScraperException;
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
        // make use of data.gv.at ckan api, fetch packages by tag name
        // @see https://github.com/okfn/ckan-api-blueprint/blob/master/blueprint_src.md#package-search-api3actionpackage_search
        // sort order is actually irrelevant as every single package needs to be checked anyway
        $tag = "Ausschreibung";
        $params = [
            'fq' => "tags:$tag",
            'rows' => 20,
            'start' => 0
        ];

        $base = "https://www.data.gv.at/katalog/api/3/action/package_search";
        $total = 0;

        while (true) {
            $this->checkForInterrupts();

            $url = $base . '?' . http_build_query($params);

            // go get 'em
            $data = $this->fetch($url);

            // do check the content here for "success" state
            // as per api documentation:
            // The API aims to always return 200 OK as the status code of its HTTP response,
            // whether there were errors with the request or not, so it's important to
            // always check the value of the "success" key in the response dictionary
            // and (if success is false) check the value of the "error" key.
            if (!isset($data->success) || !$data->success) {
                $errorMessage = isset($data->error) && isset($data->error->message) ?
                    $data->error->message : "";

                $this->error("CKAN API call not successful ($errorMessage)");
                break;
            }

            // The following structure is expected
            // $data {
            //    ...
            //    "result": {
            //       ...
            //       "results": [
            //          <package1>,
            //          <package2>,
            //          ...
            //       ]
            //    }
            // }
            $packages = $data->result->results;

            // no more packages to process (empty array received)
            if (!count($packages)) {
                break;
            }

            // keep track of the number of received packages
            $total += count($packages);

            // handle the current block of packages
            $this->updatePublishers($packages);

            // no more packages to come? exit loop
            if (count($packages) != $params['rows'] || $data->result->count == $total) {
                break;
            }

            // move the index
            $params['start'] += count($packages);
        }
    }

    /**
     * Actually perform the request.
     *
     * @param $uri
     *
     * @return null|String
     */
    protected function fetch($uri) {
        $this->info('GET ' . $uri);

        try {
            // try to get the json
            $content = convert_to_utf8($this->scraper->getJson($uri));

            $json = json_decode($content);

            return $json;

        } catch(ScraperException $ex) {
            $this->error('Unable to fetch json: '.$ex->getMessage());
        }

        return null;
    }

    /**
     * Update publishers
     *
     * @param array $packages
     */
    protected function updatePublishers($packages) {

        foreach($packages as $package) {
            $this->checkForInterrupts();

            $xmlResources = array_filter($package->resources,function($r) {
                return strtoupper($r->format) === 'XML';
            });

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