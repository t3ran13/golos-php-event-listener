<?php



namespace GolosEventListener\app\process;


use GolosEventListener\app\AppConfig;
use GolosEventListener\app\db\DBManagerInterface;

class EventsHandlersProcess extends ProcessAbstract
{
    protected $priority = -9;
    protected $isRunning = true;
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
     * @param DBManagerInterface $DBManager
     */
    public function __construct(DBManagerInterface $DBManager)
    {
        $this->setDBManager($DBManager);

        $this->processesList = [
        ];
    }

    public function initSignalsHandlers()
    {
        pcntl_signal(SIGTERM, [$this, 'signalsHandlers']);
    }

    public function signalsHandlers($signo, $pid = null, $status = null)
    {
        echo PHP_EOL . ' --- process with pid=' . $this->getPid() . ' got signal=' . $signo;

        switch ($signo) {
            case SIGTERM:
                $this->setStatus(ProcessInterface::STATUS_STOP);
                foreach ($this->processesList as $process) {
                    if (in_array($process->getStatus(), ['run', 'running'])) {
                        $processPid = $process->getPid();
                        posix_kill($processPid, SIGTERM);
                        echo PHP_EOL . ' --- to process with id=' . $processPid . ' sent signal ' . SIGTERM;
//                        pcntl_waitpid($pid, $status);
//                        echo PHP_EOL . ' --- from process with id=' . $process->getPid() . ' got signal ' . $status;
                    }
                }
                $this->isRunning = false;
                break;
            default:
        }
    }


    public function start()
    {
        echo PHP_EOL . ' --- ' . get_class($this) . ' is started';
        $this->init();

        while ($this->isRunning) {
            echo PHP_EOL . '--- EventsHandlersProcess is running';
            $this->setLastUpdateDatetime(date('Y:m:d H:i:s'));


            $listeners = $this->getDBManager()->listenersListGet();
            foreach ($listeners as $listener) {
//            $this->getDBManager()->listenerAdd($listener['conditions'], $listener['handler']);
            }

            pcntl_signal_dispatch();
            sleep(1);
        }

        //init connect to db
        // get listeners list
        $this->setStatus(ProcessInterface::STATUS_STOPPED);

        echo PHP_EOL . ' -- end task';
    }
}