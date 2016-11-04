# basebuilder/scheduling
basebuilder/scheduling can be used for easily performing cron jobs in PHP.

[![Latest Stable Version](https://poser.pugx.org/basebuilder/scheduling/v/stable)](https://packagist.org/packages/basebuilder/scheduling)
[![Total Downloads](https://poser.pugx.org/basebuilder/scheduling/downloads)](https://packagist.org/packages/basebuilder/scheduling)
[![License](https://poser.pugx.org/basebuilder/scheduling/license)](https://packagist.org/packages/basebuilder/scheduling)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/clansman-nl/scheduling/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/clansman-nl/scheduling/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/clansman-nl/scheduling/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/clansman-nl/scheduling/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/clansman-nl/scheduling/badges/build.png?b=master)](https://scrutinizer-ci.com/g/clansman-nl/scheduling/build-status/master)

## Install

Via Composer

``` bash
$ composer require basebuilder/scheduling
```

## Usage

Let's say you create `/var/php/cron.php` with the following contents:

```PHP
<?php

// load autoloader
require_once(__DIR__ . '/vendor/autoload.php');

// Define your schedule
$schedule = new \Basebuilder\Scheduling\Schedule();
$schedule
    ->run('echo "hello world"')
    ->everyFiveMinutes();

// run all commands that need to be ran
foreach ($schedule->dueEvents() as $event) {
    $event->run();
}
```

You can now easily add a single entry to the crontab:
`* * * * * /path/to/php /var/php/cron.php`

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email :author_email instead of using the issue tracker.

## Credits

- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.