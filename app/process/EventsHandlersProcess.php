<?php


namespace GolosPhpEventListener\app\process;


use GolosPhpEventListener\app\AppConfig;
use GolosPhpEventListener\app\db\RedisManager;
use GolosPhpEventListener\app\handlers\HandlerInterface;

class EventsHandlersProcess extends ProcessAbstract
{
    protected $priority  = 10;
    protected $isRunning = true;
    /**
     * @var null|AppConfig
     */
    public $appConfig;
    /**
     * @var ProcessInterface[]|HandlerInterface[]
     */
    public $processesList = [];
    protected $dbManagerClassName;


    /**
     * MainProcess constructor.
     *
     * @param string $dbManagerClassName
     */
    public function __construct($dbManagerClassName = null)
    {
        parent::__construct();
        $this->dbManagerClassName = $dbManagerClassName === null
            ? 'GolosPhpEventListener\app\db\RedisManager' : $dbManagerClassName;
    }

    /**
     * run before process start
     *
     * @return void
     */
    public function init()
    {
        $this->setDBManager(new $this->dbManagerClassName());
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
        pcntl_setpriority($this->priority, getmypid());

        echo PHP_EOL . get_class($this) . ' is started';

        while ($this->isRunning) {
            echo PHP_EOL . 'EventsHandlersProcess is running';
            $this->setLastUpdateDatetime(date('Y.m.d H:i:s'));


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
                    $this->processesList[] = $processObj;

                    echo PHP_EOL . ' --- ' . get_class($processObj) . ' is init';
                }
            }

            foreach ($this->processesList as $process) {
                if ($process->isStartNeeded()) {
                    $pid = $this->forkProcess($process);

                    if ($pid > 0) {
                        $process->setPid($pid);
                        echo PHP_EOL . date('Y.m.d H:i:s') . ' LISTENER ID=' . $process->getId() . ' is started with pid=' . $pid;
                    } else {
                        echo PHP_EOL . date('Y.m.d H:i:s') . ' CANT RUN LISTENER ID=' . $process->getId();
                    }
                } elseif ($process->isStopNeeded()) {
                    echo PHP_EOL . ' --- to process with pid ' . $process->getPid() . ' was sent stop signal=' . SIGTERM;
                    posix_kill($process->getPid(), SIGTERM);
                }
            }

            sleep(2);






            $pid = 1;
            while ($pid > 0) {
                $pid = pcntl_waitpid(-1, $pidStatus, WNOHANG);
                if ($pid > 0) {
                    echo PHP_EOL . date('Y.m.d H:i:s') . ' process with pid=' . $this->getPid() . ' from child with pid=' . $pid . ' got status=' . $pidStatus;

                    $isRestartNeeded = false;
                    if(pcntl_wifexited($pidStatus)) {
                        $code = pcntl_wexitstatus($pidStatus);
                        print " and returned exit code: $code\n";
                    }
                    else {
                        $isRestartNeeded = true;
                        print " and was unnaturally terminated and will be restarted \n";
                    }

                    //if process need restart
                    if ($isRestartNeeded) {
                        /** @var ProcessInterface|HandlerInterface|null $process */
                        $process = null;
                        foreach ($this->processesList as $processObj) {
                            if ((int)$processObj->getPid() === $pid) {
                                $process = $processObj;
                                break;
                            }
                        }
                        $process->setStatus(ProcessInterface::STATUS_RUN);

                        echo PHP_EOL . date('Y.m.d H:i:s') . ' LISTENER ID=' . $process->getId() . ' was updated to status=' . ProcessInterface::STATUS_RUN;
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
//        unset($this->processesList);
//        unset($this->dbManager);
    }
}