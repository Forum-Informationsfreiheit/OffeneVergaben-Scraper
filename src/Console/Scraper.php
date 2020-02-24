<?php

namespace OffeneVergaben\Console;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Str;
use OffeneVergaben\Console\Exceptions\ScraperException;

/**
 * Class Scraper
 * @package OffeneVergaben\Console
 *
 * Executes HTTP Requests and handles Request Exceptions.
 *
 * Operates on a 1.5 second interval by default.
 * @see $options['interval']
 *
 * Available options
 * @see createDefaultOptions()
 *
 * Provides helper function for xml requests.
 * @see getXml(...)
 *
 *  * Provides helper function for json requests.
 * @see getJson(...)
 *
 *
 * Not yet implemented:
 * - Request re-try functionality look into HandlerStack, e.g. https://stackoverflow.com/questions/38614534/how-to-check-if-endpoint-is-working-when-using-guzzlehttp/38622219#38622219
 * - Log requests functionality, log each http request with timestamp, url, status code and content to db/file
 */
class Scraper
{
    /**
     * @var Client|null $client
     */
    protected $client;

    /**
     * @var array $options
     */
    protected $options;

    /**
     * @var Request $lastRequest
     */
    protected $lastRequest;

    /**
     * @var Carbon $lastRequestAt
     */
    protected $lastRequestAt = null;

    /**
     * @var Request $lastResponse
     */
    protected $lastResponse;

    /**
     * @param Client|null $client
     * @param array $options
     * @throws ScraperException
     */
    public function __construct(Client $client = null, $options = []) {
        if (!$client) {
            $client = $this->createDefaultClient();
        }

        // set the guzzle client
        $this->client = $client;

        // set the scraper options
        $this->options = array_merge($this->createDefaultOptions(), $options);

        // sanity check
        if ($this->options['interval'] < 200) {
            throw new ScraperException('Don\'t flood the servers with requests please. Note that the interval is set in milliseconds, lowest == 200ms == 0.2sec');
        }
    }

    /**
     * GET
     *
     * Returns the full response object.
     *
     * Response body is available at $response->getBody() use typecasting to String
     * to get the full body immediately.
     *
     * @param $uri
     * @return null|\Psr\Http\Message\ResponseInterface
     * @throws ScraperException
     */
    public function get($uri) {
        // reset stored request/response data on each new request
        $this->lastRequest = null;
        $this->lastResponse = null;

        // execute the request
        $response = $this->request('GET', $uri);

        if (!$response) {
            throw new ScraperException("No response received.");
        }

        if ($response->getStatusCode() !== 200) {
            throw new ScraperException("Address not available. Status Code " . $response->getStatusCode());
        }

        return $response;
    }

    /**
     * Wrapper specifically for xml requests.
     *
     * NOTE: returns the response *BODY*
     *       the original response object is available at $this->lastResponse.
     *
     * NOTE(2): does not force or check the character encoding of the returned xml
     *          this needs to be handled by the caller.
     *
     * @param $uri
     * @return null|\Psr\Http\Message\ResponseInterface|string - the actual xml string in case of success
     * @throws ScraperException
     */
    public function getXml($uri) {
        $response = $this->get($uri);

        // read the whole response body into memory
        $body = (String)$response->getbody();

        if (!$body) {
            throw new ScraperException("Empty body received.");
        }

        // check content type header
        $ctHeaderString = join(';', $response->getHeader('Content-Type'));
        if (strpos($ctHeaderString, 'application/xml') === FALSE
            && strpos($ctHeaderString, 'text/xml') === FALSE) {
            throw new ScraperException("Wrong Content-Type received: " . $ctHeaderString);
        }

        return $body;
    }

    /**
     * Get json
     *
     * NOTE: returns the response *BODY*
     *       the original response object is available at $this->lastResponse.
     *
     * @param $uri
     * @return mixed
     * @throws ScraperException
     */
    public function getJson($uri) {
        $response = $this->get($uri);

        // read the whole response body into memory
        $body = (String)$response->getbody();

        if (!$body) {
            throw new ScraperException("Empty body received.");
        }

        // check content type header
        $ctHeaderString = join(';', $response->getHeader('Content-Type'));
        if (strpos($ctHeaderString, 'application/json') === FALSE) {
            throw new ScraperException("Wrong Content-Type received: " . $ctHeaderString);
        }

        return $body;
    }

    /**
     * Execute the request
     *
     * @param $method
     * @param $uri
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    protected function request($method, $uri) {
        $this->applyInterval();

        $response = null;

        try {
            // store timestamp of last executed request
            $this->lastRequestAt = Carbon::now();

            // use safe_urlencode (don't encode anything that could break the syntax of a url)
            $uri = $this->options['safe_urlencode'] ? safe_urlencode($uri) : $uri;

            // prepare the request
            $this->lastRequest = new Request($method, $uri, [
                'User-Agent' => $this->options['user_agent']
            ]);

            // execute
            $this->lastResponse = $this->client->send($this->lastRequest);

            return $this->lastResponse;

        } catch (RequestException $ex) {
            app()->getLogger()->addError('Scraper RequestException occurred', [
                'uri'  => $method . ' ' .$uri,
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'code' => $ex->getCode(),
                'trace' => $ex->getTraceAsString()
            ]);

            // TODO improve error handling
            // currently there is only one single request if it fails, the error is logged and the execution is done.
            // should implement re-try functionality, to re-try x number of times before giving up.


            return $ex->getResponse();

        } finally {
            if ($this->options['log_requests']) {
                $this->logRequest($uri, $response);
            }
        }
    }

    /**
     * check interval, pause if necessary
     */
    protected function applyInterval() {
        if ($this->lastRequestAt) {
            $threshold = $this->lastRequestAt->copy()->addMilliSeconds($this->options['interval']);

            if ($threshold->greaterThan(Carbon::now())) {
                $diff = Carbon::now()->diffInMicroseconds($threshold);
                usleep($diff);
            }
        }
    }

    /**
     *
     */
    protected function logRequest() {
        // TODO
    }

    /**
     * Instantiate a new guzzle Client.
     *
     * Configuration options @see http://docs.guzzlephp.org/en/stable/request-options.html
     *
     * @return Client
     */
    protected function createDefaultClient() {
        $client = new Client([
            'allow_redirects' => true,   // @see http://docs.guzzlephp.org/en/stable/request-options.html#allow-redirects
            'timeout' => 30,             // @see http://docs.guzzlephp.org/en/stable/request-options.html#timeout
        ]);

        return $client;
    }

    /**
     * @return array
     */
    protected function createDefaultOptions() {
        return [
            'user_agent'     => Application::NAME . ' ' . Application::VERSION . ' Scraper',
            'interval'       => 1500, // time in milliseconds that needs to pass between subsequent requests
                                      // 1000 milliseconds = 1 second
            'log_requests'   => false,
            'safe_urlencode' => true,
        ];
    }

    /**
     * @return Request
     */
    public function getLastRequest() {
        return $this->lastRequest;
    }

    /**
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function getLastResponse() {
        return $this->lastResponse;
    }
}