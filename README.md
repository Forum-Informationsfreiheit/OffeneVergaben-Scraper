# OffeneVergaben
OffeneVergaben.at

Here, you find the code and documentation of the OffeneVergaben.at, a platform that scrapes, aggregate, analyses and visualises open data on public procurement by authorities, state entities and state-owned companies in Austria. 

The platform focuses in particular on data on the award of contracts. Only data on contacts above EUR 50,000 is avaialble. 
This data has to be published via the Austrian open data portal https://data.gv.at (https://www.data.gv.at/suche/?searchterm=&tagFilter_sub%5B%5D=Ausschreibung) in line with the Federal Procurement Act 2018 (Bundesvergabegesetz 2018, https://www.ris.bka.gv.at/GeltendeFassung.wxe?Abfrage=Bundesnormen&Gesetzesnummer=20010295)

This project is made possible by support from Netidee (https://www.netidee.at/).

More information on the project is available at https://www.netidee.at/offene-vergaben (in German).

This is a project of Forum Informationsfreiheit – the Freedom of Information Forum Austria. 
In case you have any questions, feel free to write to office@informationsfreiheit.at

# OffeneVergaben Scraper

A command line utility toolkit, written in `php`, for scraping raw `xml` data for **Ausschreibungen laut BVergG2018**.

For a fully functional scraper a connected MySql (or MariaDB equivalent) database is required.

However there is a download command available for downloading `xml` files into a local directory, no database setup is required for this.

## Installation instructions

### Prerequisites

- [PHP >= 7.1.3](https://www.php.net/)
- [Composer](https://getcomposer.org/)
- [MySql >= 5.6](https://dev.mysql.com/doc/refman/5.6/en/installing.html)


### Setup

1. Clone the github repository
2. Navigate into the project root directory
3. Install dependencies with `composer install`
4. Run the provided `install.sql` script in `<projectroot>/sql` 
to setup the database and necessary tables
5. To configure the database connection copy the provided example configuration file ```cp .env.example .env``` and fill in your database credentials

### Run the command line tool

Inside the project directory run `bin/console` to run the command line tool in `default mode` which will print out all available commands.

Run `bin/console <command>` to run a specific command.

### Primary commands

[``scrape:all``](#command-scrape-all)

[``scrape:publishers``](#command-scrape-publishers)

[``scrape:kerndaten``](#command-scrape-kerndaten)

[``download``](#command-download)

#### Additional Commands

[``list:publishers``](#command-list-publishers)

[``enable:publisher``](#command-enable-publisher)

[``disable:publisher``](#command-disable-publisher)

#### Command Scrape All

`scrape:all`

A "meta" command that executes `scrape:publishers` and `scrape:kerndaten` consecutively. If you intend to run the scraper periodically this is the command to plug into your `crontab`.

#### Command Scrape Publishers

`scrape:publishers`

Scrapes a list of data publishers from data.gv.at . Makes use of its *CKAN API*. The main goal of this command is to retrieve all the urls of all available *Kerndaten*-sources.

Automatically runs a check to test if the provided url is a valid *Kerndaten*-source. In case the test fails the publisher will be marked as disabled in the database and will be ignored during the actual *Kerndaten* scraping process.

Please note if you don't use `scrape:all` this command is required to run before `scrape:kerndaten`.

#### Command Scrape Kerndaten

`scrape:kerndaten`

This command scrapes the actual *Kerndaten* (Tender procedures) by a two step process.

1. Scrape one *Kerndaten* Source XML to receive the complete list of *Kerndaten* item urls. Compare the list of items with already known *Kerndaten* (stored inside the database) and ignore items that have not been updated since the last run.

2. Iterate over the resulting list of urls, scrape one singular *Kerndaten* XML at a time and store the XML string in the database.

(Repeats for each active publisher)

By default the scraper waits 1.5 seconds between each request. This translates to a runtime of approximately 4 hours for the initial run (roughly 10.000 xmls, Feb. 2020).

The scraping process can be stopped at any time with `CTRL^C`. On the next call the scraper will resume seamlessly without loss of data.

#### Command Download

`download`

The "quick & dirty" way to get the data. Executes a complete download (not version aware!) of all available *Kerndaten* xmls at the given time.

By default the data will be downloaded into a timestamped directory inside the `downloads` directory.

Database setup is not required for this command to work.


#### Command List Publishers

`list:publishers`

Print a list of all known publishers that are stored in the connected database to the console and show information about the date it was added, the current status (enabled / disabled) and the key (the reference id from data.gv.at).

Use the `--check` option to perform a live check to find out if the data source is actually up and responding with xml content, this takes a moment or two, depending on the number of requests that have to be performed and the response time of each request.

#### Command Enable Publisher

`enable:publisher <reference_id of publisher>`

Use this command to (re-)enable a previously disabled publisher. Only publishers that are enabled will be considered for scraping.

#### Command Disable Publisher

`disable:publisher <reference_id of publisher>`

Use this command to disable a publisher. Disabled publishers are simply ignored during scraping.

## License

you may find the license for the source code in the `LICENSE` file.