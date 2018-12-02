# php-scheduler
A simple solution for scheduling tasks in php.


## Installation
To install via composer just run
```bash
  composer require rumd3x/php-scheduler
```

## Usage
This utility shall not be called more than once per minute without extra measures to prevent running the same task more than once. All other locks and multiple php instance handling are already built-in.

When a task runs it prints:
```
[d/m/Y H:i:s]: Job Default Started
[d/m/Y H:i:s]: Job Default Finished
```

Basic Usage:
```php
use Rumd3x\Scheduler\Schedule;

Schedule::action(function() {
    // Your code here
})->cron('* * * * *')->run();
```

You can add a parameter to the closure to use the current Cron Expression Object.
```php
use Cron\CronExpression;
use Rumd3x\Scheduler\Schedule;

Schedule::action(function(CronExpression $cron) {
    // Your code here
})->everyMinute()->run();
```

You can also give your Task a name so it prints the Task Name instead of "Default".
```php
use Cron\CronExpression;
use Rumd3x\Scheduler\Schedule;

Schedule::action(function(CronExpression $cron) {
    // Your code here
})->setName('Test')->daily()->at('11:00')->run();

/* Prints:
    [d/m/Y H:i:s]: Job Test Started
    [d/m/Y H:i:s]: Job Test Finished
*/
```

There are also prettier built in methods to help scheduling tasks without having to make cron expressions.
```php
->cron('* * * * *');	      // Run the task on a custom Cron schedule
->monthly();	              // Run the task every month
->monthly(13);	            // Run the task every 13th day of the month
->weekly()                  // Run the task every week
->weekly(1);	              // Run the task every Monday
->daily();                  // Run the task every Day
->hourly();                 // Run the task every Hour
->hourly(15);               // Run the task every Hour at minute 15
->everyThirtyMinutes();     // Run the task every Thirty Minutes
->everyFifteenMinutes();    // Run the task every Fifteen Minutes
->everyTenMinutes();        // Run the task every Ten Minutes
->everyFiveMinutes();       // Run the task every Five Minutes
->everyMinute();            // Run the task every Minute
```

There is also the "at" method that allows your to specify the time the task will be run for scheduler with intervals greater than or equal to one day.
```php
->monthly()->at('12:00');	      // Run the task every month at 12:00
->weekly()->at('8:00')          // Run the task every week at 8:00
->daily()->at('9:00');          // Run the task every Day at 9:00
```
