<?php



namespace GolosEventListener\app\process;


use GolosEventListener\app\AppConfig;
use GolosEventListener\app\db\DBManagerInterface;

class MainProcess extends ProcessAbstract
{
    protected $priority = 20;
    /**
     * @var null|AppConfig
     */
    public $appConfig;
    /**
     * @var ProcessInterface[]
     */
    public $processesList = [];

    /**
     * MainProcess constructor.
     *
     * @param AppConfig          $appConfig
     * @param DBManagerInterface $DBManager
     */
    public function __construct(AppConfig $appConfig, DBManagerInterface $DBManager)
    {
        $this->appConfig = $appConfig;
        $this->setDBManager($DBManager);

        $dbClass = get_class($DBManager);
        $this->processesList = [
            new BlockchainExplorerProcess(new $dbClass)
        ];
    }

    /**
     * update params and listeners list
     */
    public function init()
    {
        $listeners = $this->appConfig->getListenersList();
        $this->getDBManager()->listenersListClear();
        foreach ($listeners as $event => $handler) {
            $this->getDBManager()->listenerAdd($event, $handler);
        }



//        $processDBId = $this->getDBManager()->processAdd($this, ['status' => 'run']);
//        $this->setId($processDBId);

        $this->getDBManager()->processesListClear();
        foreach ($this->processesList as $process) {
            $processDBId = $this->getDBManager()->processAdd($process, ['status' => 'run']);
            $process->setId($processDBId);
        }
    }

    public function initSignalsHandlers()
    {
        // TODO: Implement initSignalsHandlers() method.
    }


    public function start()
    {
        echo PHP_EOL . ' --- ' . get_class($this) . ' is started';
        pcntl_setpriority($this->priority);
        $this->init();

        $n = 0;
        while (true) {
            $listProcessFromDB = $this->getDBManager()->processesListGet();
//            echo PHP_EOL . ' --- list ' . count($listProcessFromDB);

            foreach ($listProcessFromDB as $processId => $process) {
                foreach ($this->processesList as $processObj) {
//                        echo PHP_EOL . '$processObj ' . print_r($processObj, true);
//                        echo PHP_EOL . '$processObj->getId() ' . $processObj->getId();
//                        echo PHP_EOL . '$processId ' . $processId;
//                    echo PHP_EOL . ' if ($processObj->getId() === $processId) { ' . $processObj->getId() .'/'. $processId;
                    if ($processObj->getId() === $processId) {
                        $processObj->setPid($process['pid']);
                        $processObj->setStatus($process['status']);
                        $processObj->setLastUpdateDatetime($process['last_update_datetime']);

                        echo PHP_EOL . ' --- process with id=' . $processId . ' is updated';

                        continue(2);
                    }

                    /** @var ProcessInterface $processObj */
                    $dbClass = get_class($this->getDBManager());
                    $processObj = new $process['handler'](new $dbClass);
                    $processObj->setId($processId);
                    $processObj->setPid($process['pid']);
                    $processObj->setStatus($process['status']);
                    $processObj->setLastUpdateDatetime($process['last_update_datetime']);
                    $this->processesList[] = $processObj;

                    echo PHP_EOL . ' --- ' . get_class($processObj) . ' is init';
                }
            }
//            echo PHP_EOL . ' --- ' . count($this->processesList) . ' counter';
            foreach ($this->processesList as $process) {
//                echo PHP_EOL . '<pre>' . print_r($process, true) . '<pre>';
                if (
                    $process->getStatus() === 'run'
                    && $process->getLastUpdateDatetime() === ''
                ) {
                    $pid = $this->forkProcess($process);
                    $this->getDBManager()->processUpdateById($process->getId(), ['pid' => $pid]);

                    echo PHP_EOL . ' --- ' . get_class($process) . ' is started with id=' . $process->getId();
                } elseif($process->getStatus() === 'stop') {
//                    echo PHP_EOL . ' --- ' . get_class($process) . ' is running with pid ' . $process->getPid();
                }
            }
            $n++;
            if ($n >= 20) {
                break;
            }
            sleep(1);
        }

        //init connect to db
        // get listeners list

        echo PHP_EOL . ' -- end task';
    }
}