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

#### Database manager
Lib has ready for using RedisManager (DB structure see below), which you can to extend and add new necessary functions for your events handlers, as below

```php
<?php
namespace MyApp;

class RedisManager extends \GolosPhpEventListener\app\db\RedisManager
{
    public function connect()
    {
        if ($this->connect === null) {
            $this->connect = new \Predis\Client(
                [
                    'scheme'             => 'tcp',
                    'host'               => 'redis',
                    'port'               => 6379,
                    'database'           => 0,
                    'read_write_timeout' => -1,
                    'async'              => false,
                    'password'           => getenv('REDIS_PSWD')
                ]
            );
        }

        return $this->connect;
    }
    
    public function you_func()
    {
        //some code
    }
}
```

or you can create onw DB manager

```php
<?php
namespace MyApp;

use GolosPhpEventListener\app\db\DBManagerInterface;

class MyManager implements DBManagerInterface
{
    public function connect() 
    {
        if ($this->connect === null) {
            $this->connect = new \Predis\Client(
                [
                    'scheme'             => 'tcp',
                    'host'               => 'redis',
                    'port'               => 6379,
                    'database'           => 0,
                    'read_write_timeout' => -1,
                    'async'              => false,
                    'password'           => getenv('REDIS_PSWD')
                ]
            );
        }

        return $this->connect;
    }
    /**
         * add new event listener
         *
         * @param int   $id
         * @param array $options
         *
         * @return void
         */
    public function listenerAdd($id, $options) {
     // TODO: Implement listenerAdd() method.
    }
    /**
         * remove all events from listeners list
         *
         * @return void
         */
    public function listenersListClear() {
     // TODO: Implement listenersListClear() method.
    }
    /**
         * get listeners list
         *
         * @return array
         */
    public function listenersListGet() {
     // TODO: Implement listenersListGet() method.
    }
    /**
         * update listener data
         *
         * @param int   $id
         * @param array $options
         *
         * @return mixed
         */
    public function listenerUpdateById($id, $options) {
     // TODO: Implement listenerUpdateById() method.
    }
    /**
         * get listener data by id
         *
         * @param int         $id
         * @param null|string $field
         *
         * @return mixed
         */
    public function listenerGetById($id, $field = null) {
     // TODO: Implement listenerGetById() method.
    }
    /**
         * @param int   $id
         * @param array $options
         *
         * @return int process id in db
         */
    public function processAdd($id, $options) {
     // TODO: Implement processAdd() method.
    }
    /**
         * update process data
         *
         * @param int   $id
         * @param array $options
         *
         * @return mixed
         */
    public function processUpdateById($id, $options) {
     // TODO: Implement processUpdateById() method.
    }
    /**
         * get process data by id
         *
         * @param int         $id
         * @param null|string $field
         *
         * @return mixed
         */
    public function processInfoById($id, $field = null) {
     // TODO: Implement processInfoById() method.
    }
    /**
         * remove all process from list
         *
         * @return void
         */
    public function processesListClear() {
     // TODO: Implement processesListClear() method.
    }
    /**
         * get processes list
         *
         * @return array
         */
    public function processesListGet() {
     // TODO: Implement processesListGet() method.
    }
    /**
         * remove all events from list
         *
         * @return void
         */
    public function eventsListClear() {
     // TODO: Implement eventsListClear() method.
    }
    /**
         * @param int   $listenerId
         * @param array $trx
         *
         * @return bool status
         */
    public function eventAdd($listenerId, $trx) {
     // TODO: Implement eventAdd() method.
    }
    /**
         * @param int $listenerId
         * @param int $blockN
         * @param int $trxInBlock
         *
         * @return mixed status
         */
    public function eventDelete($listenerId, $blockN, $trxInBlock) {
     // TODO: Implement eventDelete() method.
    }
    /**
         * @param int $listenerId
         *
         * @return int
         */
    public function eventsCountByListenerId($listenerId) {
     // TODO: Implement eventsCountByListenerId() method.
    }
    /**
         * @param int $listenerId
         *
         * @return array as key => trx
         */
    public function eventsListByListenerId($listenerId) {
     // TODO: Implement eventsListByListenerId() method.
    }
    /**
         * insert error to error log list
         *
         * @param int    $id
         * @param string $error
         *
         * @return mixed
         */
    public function listenerErrorInsertToLog($id, $error) {
     // TODO: Implement listenerErrorInsertToLog() method.
    }
    /**
         * insert error to error log list
         *
         * @param int    $id
         * @param string $error
         *
         * @return mixed
         */
    public function processErrorInsertToLog($id, $error) {
     // TODO: Implement processErrorInsertToLog() method.
    }
    
}
```

#### Events handlers
Each Handler have to implements HandlerInterface and ProcessInterface, you can create onw or extends from HandlerAbstract.
When events are be found function start() will be called.

```php
<?php
namespace MyApp;

use GolosPhpEventListener\app\handlers\HandlerAbstract;


class VoteHandler extends HandlerAbstract
{
    protected $priority = 15;

    /**
     * run before process start
     *
     * @return void
     */
    public function init()
    {
        $this->setDBManager(new RedisManager());
    }


    public function initSignalsHandlers()
    {
        pcntl_signal(SIGTERM, [$this, 'signalsHandlers']);
        pcntl_signal(SIGHUP, [$this, 'signalsHandlers']); //restart process
    }

    public function signalsHandlers($signo, $signinfo)
    {
        echo PHP_EOL . ' --- process with pid=' . $this->getPid() . ' got signal=' . $signo . ' and signinfo='
            . print_r($signinfo, true);

        switch ($signo) {
            case SIGTERM:
                $this->isRunning = false;
                break;
            default:
        }
    }

    public function start()
    {
        pcntl_setpriority($this->priority, getmypid());

        //some code
    }
}
```



#### Start script for cron
When we have DB manager and event handler and can create app

```php
<?php
namespace MyApp;

use GolosPhpEventListener\app\AppConfig;
use GolosPhpEventListener\app\process\BlockchainExplorerProcess;
use GolosPhpEventListener\app\process\EventsHandlersProcess;
use GolosPhpEventListener\app\process\MainProcess;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
define('PATH', __DIR__);
require __DIR__ . "/Autoloader.php"; // only in GrapheneNodeClient project
require __DIR__ . '/vendor/autoload.php';




echo PHP_EOL . '------ StartApp.php ------' . PHP_EOL;

$appConfig = new AppConfig();
//$appConfig->addListener(['op:1:voter' => 'fafnur', 'op:0' => 'vote'], new PostIsCreatedHandler()); //from defined user
$appConfig->addListener(['op:0' => 'vote'], new PostIsCreatedHandler());

$dbRedis = New MyManager();
$mainProcess = new MainProcess(
    $appConfig,
    $dbRedis
);
//$mainProcess->ClearAllData();


$currentDatetime = (new \DateTime())->sub(new \DateInterval('PT0H30M'))->format('Y-m-d H:i:s');
if (
    $mainProcess->getStatus() === ProcessInterface::STATUS_STOPPED
    || $mainProcess->getStatus() === null
    || (
        $mainProcess->getStatus() === ProcessInterface::STATUS_RUNNING
        && $currentDatetime > $mainProcess->getLastUpdateDatetime()
    )
) {
    echo PHP_EOL . '------ new MainProcess is started ------';

    $dbClassName = get_class(New RedisManager());
    $connectorClassName = 'GrapheneNodeClient\Connectors\WebSocket\GolosWSConnector';
    $blockchainExplorerProcess = new BlockchainExplorerProcess($dbClassName, $connectorClassName);
    $blockchainExplorerProcess->setLastBlock(16238400);
    
    $mainProcess->processesList = [
        $blockchainExplorerProcess, //search events
        new EventsHandlersProcess($dbClassName) //calls events handlers
    ];

    try {
        $mainProcess->start();

    } catch (\Exception $e) {

        $msg = '"' . $e->getMessage() . '" ' . $e->getTraceAsString();
        echo PHP_EOL . ' --- mainProcess got exception ' . $msg . PHP_EOL;
        $mainProcess->errorInsertToLog(date('Y-m-d H:i:s') . '   ' . $msg);

    } finally {

        $mainProcess->setStatus(ProcessInterface::STATUS_STOPPED);
        exit(1);
    }
} else {
    echo PHP_EOL . '------ other StartApp.php is working ------';
}
```

Add this script to cron.

Example of the app base on golos-php-event-listener you can see here https://github.com/t3ran13/golos-rating-auto-reward


## DB RedisManager

DB structure:
- DB0
    - {keyPrefix}:processes:{id}:last_update_datetime
    - {keyPrefix}:processes:{id}:status
    - {keyPrefix}:processes:{id}:mode
    - {keyPrefix}:processes:{id}:pid
    - {keyPrefix}:processes:{id}:handler
    - {keyPrefix}:processes:{id}:data:last_block
    
    - {keyPrefix}:listeners:{id}:last_update_datetime
    - {keyPrefix}:listeners:{id}:status
    - {keyPrefix}:listeners:{id}:mode
    - {keyPrefix}:listeners:{id}:pid
    - {keyPrefix}:listeners:{id}:handler
    - {keyPrefix}:listeners:{id}:data:last_block
    - {keyPrefix}:listeners:{id}:conditions:{n}:key
    - {keyPrefix}:listeners:{id}:conditions:{n}:value
    
    - {keyPrefix}:events:{listener_id}:{block_n}:{trx_n_in_block}
    
    
