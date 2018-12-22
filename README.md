# golos-php-event-listener
PHP event listener for STEEM/GOLOS blockchains

## Install Via Composer
```
composer require t3ran13/golos-php-event-listener
```

## Basic Usage

For working you app you need
- database manager
- events handlers
- start script for cron

Example below

```php
<?php
namespace MyApp;

use GolosPhpEventListener\app\process\BlockchainExplorerProcess;
use GolosPhpEventListener\app\process\EventsHandlersProcess;
use GrapheneNodeClient\Connectors\ConnectorInterface;
use ProcessManager\db\RedisManager;
use ProcessManager\ProcessManager;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define('PATH', __DIR__);
require __DIR__ . "/Autoloader.php"; // only in GrapheneNodeClient project
require __DIR__ . '/vendor/autoload.php';

echo PHP_EOL . '------ start GOLOS EVENT LISTENER ------' . PHP_EOL;

$db = new RedisManager();

//Main process witch starts all other peocesses
$pm = (new ProcessManager($db))
    ->setProcessName('MainProcess')
    ->setMaxRunningProcesses(3); //it is 4 total with MainProcess, MAX 512 MB RAM by default
if ($pm->hasState()) {
    $pm->loadState();
} else {
    $pm->setPriority(25)
        ->setExecutionStep(1)
        ->setMaxNTriesOfRun(0)
        ->setSecondsBetweenRuns(55)
        ->setMaxLifetimeWithoutResults(20)
        ->saveState();
}

// creating event handler
$eh1 = (new PostIsCreatedEventHandler($db))
    ->setProcessName('GEV:votesOffafnur:1')
    ->generateIdFromProcessName()
    ->addCondition('op:1:voter','golosboard') //event trigger 1
    ->addCondition('op:0','vote'); //event trigger 2
if ($eh1->hasState()) {
    $eh1->loadState();
} else {
    $eh1->setPriority(35)
        ->setExecutionStep(1)
        ->setMaxNTriesOfRun(0)
        ->setSecondsBetweenRuns(10)
        ->setMaxLifetimeWithoutResults(15)
        ->saveState();
}

// creating event handler
$eh2 = (new PostIsCreatedEventHandler($db))
    ->setProcessName('GEV:allComments:2')
    ->generateIdFromProcessName()
    ->addCondition('op:0','comment'); //event trigger 1
if ($eh2->hasState()) {
    $eh2->loadState();
} else {
    $eh2->setPriority(35)
        ->setExecutionStep(1)
        ->setMaxNTriesOfRun(0)
        ->setSecondsBetweenRuns(10)
        ->setMaxLifetimeWithoutResults(15)
        ->saveState();
}

// creating event handler
$eh3 = (new PostIsCreatedEventHandler($db))
    ->setProcessName('GEV:allVotes:3')
    ->generateIdFromProcessName()
    ->addCondition('op:0','vote'); //event trigger 1
if ($eh3->hasState()) {
    $eh3->loadState();
} else {
    $eh3->setPriority(35)
        ->setExecutionStep(1)
        ->setMaxNTriesOfRun(0)
        ->setSecondsBetweenRuns(10)
        ->setMaxLifetimeWithoutResults(15)
        ->saveState();
}


// Creating blockchain lestener
$BEP = (new BlockchainExplorerProcess($db,ConnectorInterface::PLATFORM_GOLOS))
    ->setProcessName('GEV:BlockchainExplorer')
    ->setLastBlock(16146488)
    ->addEvent($eh1) //do not forget add eventhandlers to explorer process
    ->addEvent($eh2) //adding event handler for detecting events
    ->addEvent($eh3);
if ($BEP->hasState()) {
    $BEP->loadState();
} else {
    $BEP->setPriority(30)
        ->setExecutionStep(1)
        ->setMaxNTriesOfRun(0)
        ->setSecondsBetweenRuns(30)
        ->setMaxLifetimeWithoutResults(20)
        ->saveState();
}

$pm->addProcess($BEP)
    ->addProcess($eh1)//add event listener for handling events
    ->addProcess($eh2)
    ->addProcess($eh3);
$pm->start();

```

Add this script to cron.


#### Database manager
Lib use ready for using RedisManager from [t3ran13/php-process-manager](https://github.com/t3ran13/php-process-manager) composer package, with next DB structure:


```
- DB0
    - PM:GEV:{ProcessName}:{id}:className
    - PM:GEV:{ProcessName}:{id}:processName
    - PM:GEV:{ProcessName}:{id}:priority
    - PM:GEV:{ProcessName}:{id}:pid
    - PM:GEV:{ProcessName}:{id}:executionStep
    - PM:GEV:{ProcessName}:{id}:isRunning
    - PM:GEV:{ProcessName}:{id}:nTriesOfRun
    - PM:GEV:{ProcessName}:{id}:maxNTriesOfRun
    - PM:GEV:{ProcessName}:{id}:secondsBetweenRuns
    - PM:GEV:{ProcessName}:{id}:maxLifetimeWithoutResults
    - PM:GEV:{ProcessName}:{id}:lastUpdateDatetime
    - PM:GEV:{ProcessName}:{id}:data:*
    - PM:GEV:{ProcessName}:{id}:data:events:*
    - PM:GEV:{ProcessName}:{id}:errors:*
    
```

How to create onw DB manager, see [t3ran13/php-process-manager](https://github.com/t3ran13/php-process-manager)

#### Events handlers
Each Handler have to implements EventHandlerInterface and ProcessManager\process\ProcessInterface, you can create onw or extends from EventHandlerAbstract.
When any event are found BlockchainExplorer make checking with isTrxSatisfiesConditions function and save to handling list if is it satisfied.
When EventHandlerProcess starts it handle all events from queue.

```php
<?php
namespace MyApp;

use GolosPhpEventListener\app\process\handlers\EventHandlerAbstract;


class VoteHandler extends EventHandlerAbstract
{
    public function start()
    {
        $events = $this->getEvents(); //TODO FIXME
        echo PHP_EOL . date('Y-m-d H:i:s') . $this->getProcessName() . ' is running and have total events='
            . count($events);

        foreach ($events as $key => $event) {
            // some code
            $this->setLastUpdateDatetime(date('Y-m-d H:i:s'))
                ->removeEventByKey($key)
                ->saveState();
        }
    }

    /**
     * ask process to start
     *
     * @return bool
     */
    public function isStartNeeded(): bool
    {
        return parent::isStartNeeded()
            && count($this->getEvents()) > 0;
    }
}
```

### Example

Example of the app base on golos-php-event-listener you can see here https://github.com/t3ran13/golos-rating-auto-reward



    
    
