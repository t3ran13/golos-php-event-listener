<?php



namespace GolosEventListener\app\process;


use GolosEventListener\app\AppConfig;
use GolosEventListener\app\db\DBManagerInterface;

class MainProcess extends ProcessAbstract
{
    protected $priority = 5;
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

        $this->processesList = [
            new BlockchainExplorerProcess($DBManager),
            new EventsHandlersProcess($DBManager)
        ];
    }

    /**
     * update params and listeners list
     */
    public function init()
    {
        $this->getDBManager()->eventsListClear();
        $listeners = $this->appConfig->getListenersList();
        $this->getDBManager()->listenersListClear();
        foreach ($listeners as $listener) {

            $params['last_update_datetime'] = '';
            $params['status'] = ProcessInterface::STATUS_RUN;
            $params['pid'] = 0;
            $params['data:mode'] = $listener['handler']->listenerMode;

            $n = 0;
            foreach ($listener['conditions'] as $key => $value) {
                $params['conditions:' . $n . ':key'] = $key;
                $params['conditions:' . $n . ':value'] = $value;
                $n++;
            }
            $this->getDBManager()->listenerAdd($listener['handler'], $params);
        }

        $this->initSignalsHandlers();


        $this->getDBManager()->processesListClear();

        //register main process in db
        $processDBId = $this->getDBManager()->processAdd($this, ['status' => ProcessInterface::STATUS_RUNNING]);
        $this->setId($processDBId);
        $this->setPid(getmypid());

        //register processes in db
        foreach ($this->processesList as $process) {
            $processDBId = $this->getDBManager()->processAdd($process, ['status' => ProcessInterface::STATUS_RUN]);
            $process->init();
            $process->setId($processDBId);
        }

        //add main process to processes list
        array_unshift($this->processesList, $this);
    }

    public function initSignalsHandlers()
    {
        pcntl_signal(SIGTERM, [$this, 'signalsHandlers']); //kill
        pcntl_signal(SIGINT, [$this, 'signalsHandlers']); //ctrl+c
        pcntl_signal(SIGHUP, [$this, 'signalsHandlers']); //restart process
//        pcntl_signal(SIGCHLD, [$this, 'signalsHandlers']);
    }

    public function signalsHandlers($signo, $signinfo)
    {
        echo PHP_EOL . ' --- process with pid=' . $this->getPid() . ' got signal=' . $signo . ' and signinfo='
            . print_r($signinfo, true);

        switch ($signo) {
            case SIGINT:
            case SIGTERM:
                $this->setStatus(ProcessInterface::STATUS_STOP);
                foreach ($this->processesList as $process) {
                    if (in_array($process->getStatus(), ['run', 'running'])) {
                        $processPid = $process->getPid();
                        posix_kill($processPid, SIGTERM);
                        echo PHP_EOL . ' --- to process with pid=' . $processPid . ' sent signal ' . SIGTERM;
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
        pcntl_setpriority($this->priority, getmypid());

        echo PHP_EOL . ' --- ' . get_class($this) . ' is started';
        $this->init();
//        $dbClass = get_class($this->getDBManager());

        $n = 0;
        while ($this->isRunning) {
            echo PHP_EOL . '--- ' .($n++) . ' MainProcess is running';

            $this->setLastUpdateDatetime(date('Y.m.d H:i:s'));

            $listProcessFromDB = $this->getDBManager()->processesListGet();

            //get processes from db and init objects for new
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
                    $processObj = new $lastProcessInfo['handler']($this->getDBManager());
                    $processObj->init();
                    $processObj->setId($processId);
                    $this->processesList[] = $processObj;

                    echo PHP_EOL . ' --- ' . get_class($processObj) . ' is init';
                }
            }

            //handle process statuses
            foreach ($this->processesList as $process) {
                echo PHP_EOL . ' --- !!!!!' . ' pr=' . get_class($process) . ' st=' . $process->getStatus();
                if ($process->isStartNeeded()) {
                    $pid = $this->forkProcess($process);
                    $process->setPid($pid);

                    echo PHP_EOL . ' --- PROCESS ID=' . $process->getId() . ' is started with pid=' . $pid;
                } elseif($process->isStopNeeded()) {
                    echo PHP_EOL . ' --- to process with pid ' . $process->getPid() . ' was sent stop signal=' . SIGTERM;
                    posix_kill($process->getPid(), SIGTERM);
                }
            }


            sleep(1);

            //get children signals
            $pid = 1;
            while ($pid > 0) {
                $pid = pcntl_waitpid(-1, $pidStatus, WUNTRACED);
                if ($pid > 0) {
                    echo PHP_EOL . date('Y.m.d H:i:s') . ' process with pid=' . $this->getPid() . ' from child with pid=' . $pid . ' got status=' . $pidStatus;

                    if(pcntl_wifexited($pidStatus)) {
                        $code = pcntl_wexitstatus($pidStatus);
                        print " and returned exit code: $code\n";
                    }
                    else {
                        print " and was unnaturally terminated, restart process\n";
                        $process = null;
                        foreach ($this->processesList as $processObj) {
                            if ((int)$processObj->getPid() === $pid) {
                                $process = $processObj;
                                break;
                            }
                        }
                        $process->setStatus(ProcessInterface::STATUS_RUN);
                    }
                }
            }

            pcntl_signal_dispatch();
        }

        //init connect to db
        // get listeners list
        $this->setStatus(ProcessInterface::STATUS_STOPPED);

        echo PHP_EOL . ' -- end task of ' . get_class($this);
    }

    /**
     * clear parent resourses in child process
     *
     * @return void
     */
    public function clearParentResources()
    {
//        unset($this->appConfig);
//        unset($this->processesList);
//        unset($this->dbManager);
    }
}