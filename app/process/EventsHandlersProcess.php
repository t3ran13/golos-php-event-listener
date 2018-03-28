<?php



namespace GolosEventListener\app\process;


use GolosEventListener\app\AppConfig;
use GolosEventListener\app\db\DBManagerInterface;
use GolosEventListener\app\handlers\HandlerInterface;

class EventsHandlersProcess extends ProcessAbstract
{
    protected $priority = -9;
    protected $isRunning = true;
    /**
     * @var null|AppConfig
     */
    public $appConfig;
    /**
     * @var ProcessInterface|HandlerInterface[]
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
    }

    /**
     * run before process start
     *
     * @return void
     */
    public function init()
    {
        // TODO: Implement init() method.
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
        $dbClass = get_class($this->getDBManager());

        while ($this->isRunning) {
            echo PHP_EOL . '--- EventsHandlersProcess is running';
            $this->setLastUpdateDatetime(date('Y:m:d H:i:s'));


            $listenersFromDB = $this->getDBManager()->listenersListGet();

            foreach ($listenersFromDB as $listenerId => $listenerInfo) {
                /** @var ProcessInterface|HandlerInterface|null $processObj */
                $processObj = null;

                foreach ($this->processesList as $process) {
                    if ($process->getId() === $listenerId) {
                        $processObj = $process;
                        break;
                    }
                }

                if ($processObj === null) {
                    $processObj = new $listenerInfo['handler']();
                    $processObj->init();
                    $processObj->setId($listenerId);
                    $this->processesList[] = $processObj;

                    echo PHP_EOL . ' --- ' . get_class($processObj) . ' is init';
                }
            }

            foreach ($this->processesList as $process) {
                $status = $process->getStatus();
                $mode = $process->getListenerMode();

                if (
                    $status === ProcessInterface::STATUS_RUN
                    || ($status === ProcessInterface::STATUS_STOPPED && $mode === HandlerInterface::MODE_REPEAT)
                ) {
                    $pid = $this->forkProcess($process);
                    $process->setPid($pid);

                    echo PHP_EOL . ' --- LISTENER ID=' . $process->getId() . ' is started with pid=' . $pid;
                } elseif($status === ProcessInterface::STATUS_STOP) {
                    echo PHP_EOL . ' --- to process with pid ' . $process->getPid() . ' was sent stop signal=' . SIGTERM;
                    posix_kill($process->getPid(), SIGTERM);
                }
            }


            pcntl_signal_dispatch();
            sleep(1);
        }

        //init connect to db
        // get listeners list
        $this->setStatus(ProcessInterface::STATUS_STOPPED);

        echo PHP_EOL . ' -- end task of ' . get_class($this);
    }
}