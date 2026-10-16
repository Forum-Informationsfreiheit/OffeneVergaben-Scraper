# Upgrading

Steps needed on existing installations when updating the scraper. Newest changes first.

## 2026-10: dependencies for PHP 8.1, data.gv.at search API, larger `kerndaten.xml` column

### 0. PHP 8.1 and updated dependencies (install required)

The dependencies were from 2020 and declared PHP 7.1. On PHP 8.1 they printed deprecation notices on every call. They are updated to versions for PHP 8.1:

| Package | before | now |
|---|---|---|
| php | ^7.1.3 | ^8.1 (and `ext-pcntl`) |
| symfony/console | ^4.4 | ^6.4 |
| monolog/monolog | ^1.25 | ^2.9 |
| guzzlehttp/guzzle | ^6.5 | ^7.8 |
| vlucas/phpdotenv | ^4.1 | ^5.6 |
| illuminate/support | ^5.8 | ^10.48 |
| nesbot/carbon | ^2.28 | ^2.72 |

On the server, with the PHP version that runs the cron job (`php -v` there):

```sh
php -v                                  # must be 8.1 or newer
composer install --no-dev --no-interaction
```

Do this while no scrape is running: a run that starts with the old `vendor/` and loads classes from the new one fails. `.env` stays the same.

### 1. Widen `kerndaten.xml` (database change required)

`kerndaten.xml` was `TEXT`, which holds at most 65,535 bytes. Some publishers now send larger documents, e.g. vemap eForms notices of about 79 KB. MySQL rejects these, and `scrape:all` aborts on the first such document. Every source after it is skipped on every run. In the cron log this shows up as:

```
SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'xml' at row 1
```

and in syslog as `grandchild #… failed with exit status 255`.

`sql/install.sql` now uses `MEDIUMTEXT` (up to 16 MB). Existing databases need:

```sql
ALTER TABLE kerndaten MODIFY xml MEDIUMTEXT NOT NULL;
```

- Back up the database first.
- Run it while no scrape is running: disable the cron job, or pick a time between runs. MySQL rebuilds the table and blocks writes until it is done; on a large table this can take a while.
- Existing rows are not changed.

### 2. data.gv.at search API (no action needed before deploying)

data.gv.at has replaced its CKAN API (`/katalog/api/3/action/package_search`) with a new search API (`/api/hub/search/`). The old endpoint returns 404. `scrape:publishers` therefore failed and kept no publisher list up to date. `scrape:publishers` and `download` now use the new API (`src/Console/DataGvAt.php`). They search datasets with the keyword `ausschreibung`, the same set as https://www.data.gv.at/datasets?keywords=ausschreibung.

**Check after the first run:** most datasets keep their old ID, but some are now identified by a name, e.g. `metadatensatzbvergiefservicegmbh`. If an ID in `quellen.reference_id` no longer matches, `scrape:publishers` inserts the publisher again under an alias with a numeric suffix (`…_001`). The old row and its kerndaten stay untouched. Afterwards, list the publishers and look for such duplicates:

```sh
bin/console list:publishers
```

For each duplicate, either:

- **Keep the old row** (recommended: its alias stays, so the existing kerndaten stay attached to it). Note the new row's `reference_id` and `url`, delete the new row, then put both values on the old row. Do this before `scrape:kerndaten` runs, otherwise the new row already has kerndaten and cannot be deleted (foreign key):

  ```sql
  SELECT alias, reference_id, url FROM quellen WHERE alias IN ('<old_alias>', '<new_alias>');
  DELETE FROM quellen WHERE alias = '<new_alias>';
  UPDATE quellen SET reference_id = '<new_reference_id>', url = '<new_url>' WHERE alias = '<old_alias>';
  ```

- **Or switch to the new row:** `bin/console disable:publisher <old_alias>`. Kerndaten of that publisher are then scraped again under the new alias, and the app sees them as new records.

### Requirements

PHP 8.1 or newer with the `pcntl` extension (it was already needed before; `bin/console` fails with `Call to undefined function … pcntl_signal()` without it). `composer install` checks both.
