<?php

namespace OffeneVergaben\Models\DOM;

use Carbon\Carbon;
use DOMDocument;

/**
 * Kdq representation, holds an array of items.
 *
 * Class Kdq
 * @package OffeneVergaben\Models\DOM
 */
class Kdq
{
    /**
     * @var string $providerId
     */
    protected $providerId;

    /**
     * @var DOMDocument $document
     */
    protected $document;

    /**
     * @var array $items
     */
    protected $items = [];

    /**
     * @param string $quelle
     * @param string $xmlString
     */
    public function __construct($quelle, $xmlString) {
        $this->quelle = $quelle;

        // load dom
        $doc = new DOMDocument();
        $doc->loadXML($xmlString);
        $this->document = $doc;

        // load items
        $this->loadItems();
    }

    /*
     * not yet implemented: validate against xsd
     *
    public function validate($xsdPath) {
        if ($this->document->schemaValidate($xsdPath)) {
            echo "\n"."valid"."\n";
        } else {
            echo "\n"."NOT valid"."\n";
        }
    }
    */

    /**
     * @return \DOMNodeList
     */
    public function getHeader() {
        return $this->document->getElementsByTagName('header');
    }

    /**
     * @return KdqItem[]
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * @return \DOMNodeList
     */
    public function getItemNodeList() {
        return $this->document->getElementsByTagName('item');
    }

    public function getQuelle() {
        return $this->quelle;
    }

    /**
     *
     */
    protected function loadItems() {
        $items = $this->getItemNodeList();

        foreach($items as $item) {
            $this->items[] = KdqItem::build($item);
        }
    }
}

/**
 * The representation of one Kdq item
 *
 * Class KdqItem
 * @package OffeneVergaben\Models\DOM
 */
class KdqItem {

    protected $url;
    protected $id;
    protected $lastMod;

    /**
     * A key value store for any additional data one might need to reference on this item
     *
     * @var array
     */
    protected $data = [];

    private function __construct($id, $url, $lastMod) {
        $this->id  = $id;
        $this->url = $url;
        $this->lastMod = $lastMod;
    }

    /**
     * @param \DomElement $item
     * @return static
     */
    public static function build(\DomElement $item) {
        $id  = $item->hasAttribute('id') ?
            $item->getAttribute('id') : null;
        $url = $item->getElementsByTagName('url')->length ?
            $item->getElementsByTagName('url')->item(0)->nodeValue : null;
        $lastMod = $item->hasAttribute('lastmod') ?
            Carbon::createFromTimeString($item->getAttribute('lastmod')) : null;

        return new static($id, $url, $lastMod);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Carbon
     */
    public function getLastMod()
    {
        return $this->lastMod;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getData($key) {
        return $this->data[$key];
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setData($key, $value) {
        $this->data[$key] = $value;
    }
}