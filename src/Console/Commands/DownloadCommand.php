<?php

namespace OffeneVergaben\Console\Commands;

use Carbon\Carbon;
use Illuminate\Support\Str;
use OffeneVergaben\Console\Exceptions\ScraperException;
use OffeneVergaben\Console\Scraper;
use OffeneVergaben\Models\DOM\Kdq;
use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DownloadCommand
 * @package OffeneVergaben\Console\Commands
 *
 * Quick and dirty download-once of all available kerndaten data
 */
class DownloadCommand extends AbstractCommand
{
    protected $path = BASE_PATH . DIRECTORY_SEPARATOR . 'downloads';

    /**
     * @var \OffeneVergaben\Console\Scraper $scraper
     */
    protected $scraper;

    protected $now;

    protected function configure() {
        $this->setName('download');
        $this->setDescription('Quick and dirty download-once of all available kerndaten xmls.');
    }

    protected function init() {
        $this->selfHandleInterrupts();
        $this->scraper = new Scraper();

        $this->now = Carbon::now();

        if (!file_exists($this->path)) {
            mkdir($this->path);

            if (!file_exists($this->path)) {
                throw new \Exception("Unable to create downloads directory in root folder. Please check permissions.");
            }
        }
    }

    protected function exe() {
        $publishers = $this->getPublishersFromDataGvAt();

        foreach($publishers as $publisher) {
            $this->checkForInterrupts();

            $this->info('<comment>'.$publisher['name'].'</comment>');

            $kdq = $this->getKdq($publisher);

            if (!$kdq) {
                continue;
            }

            $kdq = new Kdq($publisher['alias'], $kdq);
            $items = $kdq->getItems();

            $this->info('Downloading '. count($items) . ' XMLs');

            foreach($items as $item) {
                $this->checkForInterrupts();
                $kd = $this->getKd($item);

                if (!$kd) {
                    continue;
                }

                $this->writeXml($publisher, $item, $kd);
            }
        }
    }

    /**
     * @param array $publisher
     * @return null|\Psr\Http\Message\ResponseInterface|string
     */
    protected function getKdq($publisher) {
        try {
            return $this->scraper->getXml($publisher['url']);
        } catch(ScraperException $ex) {
            $this->error("Unable to get KDQ from publisher ".$publisher['name']);
            return null;
        }
    }

    /**
     * @param \OffeneVergaben\Models\DOM\KdqItem $item
     * @return null|\Psr\Http\Message\ResponseInterface|string
     */
    protected function getKd($item) {
        try {
            return $this->scraper->getXml($item->getUrl());
        } catch(ScraperException $ex) {
            $this->error("Unable to get Kerndaten XML ".$item->getId());
            return null;
        }
    }

    /**
     * @param array $publisher
     * @param \OffeneVergaben\Models\DOM\KdqItem $kdqItem
     * @param string $kd
     * @throws \Exception
     */
    protected function writeXml($publisher, $kdqItem, $kd) {
        $downloadDir = $this->path . DIRECTORY_SEPARATOR . $this->now->format('Y-m-d_Hi');

        if (!file_exists($downloadDir)) {
            $this->createDir($downloadDir);
        }

        $filePath = $downloadDir . DIRECTORY_SEPARATOR .$publisher['alias'] . '_' . $kdqItem->getId() . '.xml';

        file_put_contents($filePath, $kd);

        if (!file_exists($filePath)) {
            throw new \Exception("Unable to write file $filePath");
        }
    }

    protected function createDir($path) {
        if (!file_exists($path)) {
            mkdir($path);

            if (!file_exists($this->path)) {
                throw new \Exception("Unable to create directory ".$path.". Please check permissions.");
            }
        }
    }

    // TODO DELETE
    protected function createFile($path) {
        if (file_exists($path)) {
            throw new \Exception("File already exists! ".$path);
        }

        $fh = fopen($path,"w");
        fwrite($fh,"");

        return $fh;
    }

    protected function getPublishersFromDataGvAt() {

        $tag = "Ausschreibung";
        $params = [
            'fq' => "tags:$tag",
            'rows' => 20,
            'start' => 0
        ];

        $base = "https://www.data.gv.at/katalog/api/3/action/package_search";
        $total = 0;

        $publishers = [];

        $this->info('Contacting data.gv.at ...');

        while (true) {
            $this->checkForInterrupts();
            $url = $base . '?' . http_build_query($params);
            $data = $this->fetchPublishersJson($url);
            if (!isset($data->success) || !$data->success) {
                $errorMessage = isset($data->error) && isset($data->error->message) ?
                    $data->error->message : "";

                $this->error("CKAN API call not successful ($errorMessage)");
                break;
            }
            $packages = $data->result->results;
            if (!count($packages)) {
                break;
            }
            $total += count($packages);
            $publishers = array_merge($publishers,$this->getPublishersFromPackages($packages));
            if (count($packages) != $params['rows'] || $data->result->count == $total) {
                break;
            }
            $params['start'] += count($packages);
        }

        return $publishers;
    }

    protected function getPublishersFromPackages($packages) {
        $publishers = [];

        foreach($packages as $package) {
            $this->checkForInterrupts();

            $xmlResources = array_filter($package->resources,function($r) {
                return strtoupper($r->format) === 'XML';
            });

            if (count($xmlResources) === 0) {
                continue;
            }
            if (count($xmlResources) > 1) {
                $this->error('Multiple XML resources found for publisher '.$package->publisher . ' proceeding with resource '.$xmlResources[0]->id . '. Other resources are ignored.');
            }

            $resource = $xmlResources[0];

            $publishers[] = [
                'name' => $package->publisher,
                'alias' => substr(Str::slug($package->publisher, '', 'de'),0,50),
                'url'  => $resource->url
            ];
        }

        return $publishers;
    }

    /**
     * @param $uri
     * @return null|String
     */
    protected function fetchPublishersJson($uri) {
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
}