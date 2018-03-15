<?php



namespace GolosEventListener\app\process;


use GolosEventListener\app\AppConfig;
use GolosEventListener\app\db\DBManagerInterface;

class MainProcess extends ProcessAbstract
{
    protected $priority = 20;
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

        $this->initSignalsHandlers();


        $this->getDBManager()->processesListClear();

        //register main process in db
        $processDBId = $this->getDBManager()->processAdd($this, ['status' => ProcessInterface::STATUS_RUNNING]);
        $this->setId($processDBId);
        $this->setStatus(ProcessInterface::STATUS_RUNNING);

        //register processes in db
        foreach ($this->processesList as $process) {
            $processDBId = $this->getDBManager()->processAdd($process, ['status' => ProcessInterface::STATUS_RUN]);
            $process->setId($processDBId);
        }

        //add main process to processes list
        array_unshift($this->processesList, $this);
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
        pcntl_setpriority($this->priority);
        $this->init();

        $n = 0;
        while ($this->isRunning) {
            echo PHP_EOL . '--- ' .($n++) . ' MainProcess is running';

            $this->setLastUpdateDatetime(date('Y:m:d H:i:s'));

            $listProcessFromDB = $this->getDBManager()->processesListGet();

            foreach ($listProcessFromDB as $processId => $lastProcessInfo) {
                /** @var ProcessInterface|null $processObj */
                $processObj = null;

                foreach ($this->processesList as $process) {
                    if ($process->getId() === $processId) {
                        $processObj = $process;
                        break;
                    }
                }

                if ($processObj === null) {
                    $dbClass = get_class($this->getDBManager());
                    $processObj = new $lastProcessInfo['handler'](new $dbClass);
                    $processObj->setId($processId);
                    $this->processesList[] = $processObj;

                    echo PHP_EOL . ' --- ' . get_class($processObj) . ' is init';
                }
            }

            foreach ($this->processesList as $process) {
                $status = $process->getStatus();
                if (
                    $status === 'run'
                ) {
                    $pid = $this->forkProcess($process);
                    $process->setPid($pid);

                    echo PHP_EOL . ' --- ' . get_class($process) . ' is started with id=' . $process->getId();
                } elseif($status === 'stop') {
                    echo PHP_EOL . ' --- to process with pid ' . $process->getPid() . ' was sent stop signal=' . SIGTERM;
                    posix_kill($process->getPid(), SIGTERM);
                }
            }

            pcntl_signal_dispatch();
            sleep(1);
        }

        //init connect to db
        // get listeners list

        echo PHP_EOL . ' -- end task';
    }
}