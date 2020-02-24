<?php

namespace OffeneVergaben\Console\Commands;

use Illuminate\Support\Str;
use OffeneVergaben\Console\Scraper;
use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class HelloWorldCommand
 * @package OffeneVergaben\Console\Commands
 *
 * Bare-bones command example
 */
class HelloWorldCommand extends AbstractCommand
{
    protected function configure() {
        $this->setName('helloworld');
        $this->setDescription('Print Hello World to the console.');
    }

    protected function exe() {
        $this->info('Hello World!');

        /*

        $scraper = new Scraper();
        $response = $scraper->get("https://www.data.gv.at/katalog/api/3/action/package_show?id=7e80bc4b-3537-42fd-881f-d9290b34782e");

        $body = (string)$response->getBody();

        // der "raw" body output enthält für die resource url:
        // https://apppool.wko.at/data/ab/10/KDQ_WKO%20Inhouse%20GmbH%20der%20Wirtschaftskammern%20\u00d6sterreichs.xml
        // notice: ist sowohl Url codiert (%20) UND enthält einen Unicode character \u00d6 für Ö
        var_dump($body);

        var_dump(json_decode($body));
        // json decodierter output der gleichen url:
        // https://apppool.wko.at/data/ab/10/KDQ_WKO%20Inhouse%20GmbH%20der%20Wirtschaftskammern%20Österreichs.xml
        // ---> !!!! man sieht json_decode hat \u00d6 zu Ö konvertiert

        echo(json_encode(['https://apppool.wko.at/data/ab/10/KDQ_WKO%20Inhouse%20GmbH%20der%20Wirtschaftskammern%20Österreichs.xml']));
        echo(json_encode('Österreich'));
        echo 'Österreich';

        */

        echo urlencode('%') . "\n";
        echo urlencode(':') . "\n";
    }

}