<?php

namespace OffeneVergaben\Console;

use OffeneVergaben\Console\Exceptions\ScraperException;

/**
 * Class DataGvAt
 * @package OffeneVergaben\Console
 *
 * Client for the data.gv.at (piveau) search API.
 * @see https://www.data.gv.at/api/hub/search/
 *
 * Replaces the former CKAN API (katalog/api/3/action/package_search) which is no longer available.
 * Datasets are returned in the shape of the former CKAN packages, so callers can keep
 * working with $package->id, $package->publisher and $package->resources[]->{id,format,url}.
 */
class DataGvAt
{
    const BASE = "https://www.data.gv.at/api/hub/search";

    /**
     * @var Scraper $scraper
     */
    protected $scraper;

    public function __construct(Scraper $scraper) {
        $this->scraper = $scraper;
    }

    /**
     * Fetch all datasets tagged with the given keyword, including their distributions.
     *
     * The search endpoint does not return distributions, so every dataset is fetched individually.
     *
     * @param string        $keyword  keyword id as used by data.gv.at (lowercase slug)
     * @param callable|null $onRequest called with each requested url (e.g. for logging)
     *
     * @return array of package objects
     *
     * @throws ScraperException
     */
    public function getPackagesByKeyword($keyword, callable $onRequest = null) {
        $params = [
            'filter' => 'dataset',
            'facets' => json_encode(['keywords' => [$keyword]]),
            'limit'  => 50,
            'page'   => 0,
        ];

        $ids = [];

        while (true) {
            $data = $this->fetch(self::BASE . '/search?' . http_build_query($params), $onRequest);

            if (!isset($data->result->results)) {
                throw new ScraperException("Unexpected search response");
            }

            foreach ($data->result->results as $dataset) {
                $ids[] = $dataset->id;
            }

            if (!count($data->result->results) || count($ids) >= $data->result->count) {
                break;
            }

            $params['page']++;
        }

        $packages = [];

        foreach ($ids as $id) {
            $data = $this->fetch(self::BASE . '/datasets/' . rawurlencode($id), $onRequest);

            if (!isset($data->result)) {
                throw new ScraperException("Unexpected dataset response for $id");
            }

            $packages[] = $this->toPackage($data->result);
        }

        return $packages;
    }

    /**
     * Map a piveau dataset onto the former CKAN package structure.
     *
     * @param object $dataset
     *
     * @return object
     */
    protected function toPackage($dataset) {
        $resources = [];

        foreach ($dataset->distributions ?? [] as $distribution) {
            $url = $distribution->access_url[0] ?? $distribution->download_url[0] ?? null;

            if (!$url) {
                continue;
            }

            $resources[] = (object)[
                'id'     => $distribution->id,
                'format' => $this->detectFormat($distribution, $url),
                'url'    => $url,
            ];
        }

        return (object)[
            'id'        => $dataset->id,
            'publisher' => $dataset->publisher->name ?? '',
            'resources' => $resources,
        ];
    }

    /**
     * Not every publisher sets the format, fall back to media type and file extension.
     *
     * @param object $distribution
     * @param string $url
     *
     * @return string
     */
    protected function detectFormat($distribution, $url) {
        if (!empty($distribution->format->id)) {
            return strtoupper($distribution->format->id);
        }

        if (!empty($distribution->media_type) && stripos($distribution->media_type, 'xml') !== false) {
            return 'XML';
        }

        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);

        return strtoupper($extension);
    }

    /**
     * @param string        $uri
     * @param callable|null $onRequest
     *
     * @return object
     *
     * @throws ScraperException
     */
    protected function fetch($uri, callable $onRequest = null) {
        if ($onRequest) {
            $onRequest($uri);
        }

        return json_decode(convert_to_utf8($this->scraper->getJson($uri)));
    }
}
