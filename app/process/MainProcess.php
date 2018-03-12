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

        $this->processesList = [
            new BlockchainExplorerProcess($DBManager)
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
            foreach ($this->processesList as $process) {
                if ($process->getPid() === null) {


                    $pid = $this->forkProcess($process);
                    $process->setPid($pid);
//
//                    pcntl_setpriority($process->getPriority(), $process->getPid());

                    echo PHP_EOL . ' --- ' . get_class($process) . ' is started with pid ' . $process->getPid();
                } else {
                    echo PHP_EOL . ' --- ' . get_class($process) . ' is running with pid ' . $process->getPid();
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