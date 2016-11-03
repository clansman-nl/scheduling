# basebuilder/scheduling
basebuilder/scheduling can be used for easily performing cron jobs in PHP.

## Examples
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