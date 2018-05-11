<?php


namespace GolosPhpEventListener\app\process;


use GolosPhpEventListener\app\db\DBManagerInterface;

abstract class ProcessAbstract implements ProcessInterface
{
    /** @var null|DBManagerInterface */
    protected $dbManager = null;
    /** @var null|int id in database */
    protected $id = null;
    /** @var null|int */
    protected $pid = null;
    /** @var null|string */
    protected $priority = 0;
    /** @var null|string */
    protected $status;
    /** @var null|string */
    protected $lastUpdateDatetime;

    /**
     * ProcessAbstract constructor.
     */
    public function __construct()
    {
        $this->setId(($this->priority + 20) . substr(md5(get_class($this)), 0, 7));
    }

    /**
     * @param DBManagerInterface $dbManager
     */
    public function setDBManager(DBManagerInterface $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * @return null|DBManagerInterface
     */
    public function getDBManager()
    {
        return $this->dbManager;
    }

    /**
     * set id, witch it have in db
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * get id, witch it have in db
     *
     * @return null|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getPid()
    {
        return $this->getDBManager()->processInfoById($this->getId(), 'pid');
    }

    /**
     * @param int|null $pid
     */
    public function setPid($pid)
    {
        $this->getDBManager()->processUpdateById($this->getId(), ['pid' => $pid]);
    }

    /**
     * @param string $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return null|string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return null|string
     */
    public function getStatus()
    {
        return $this->getDBManager()->processInfoById($this->getId(), 'status');
    }

    /**
     * @param null|string $status
     */
    public function setStatus($status)
    {
        $this->getDBManager()->processUpdateById($this->getId(), ['status' => $status]);
    }

    /**
     * @return null|string
     */
    public function getMode()
    {
        return $this->getDBManager()->processInfoById($this->getId(), 'mode');
    }

    /**
     * @param null|string $mode
     */
    public function setMode($mode)
    {
        $this->getDBManager()->processUpdateById($this->getId(), ['mode' => $mode]);
    }

    /**
     * @return null|string
     */
    public function getLastUpdateDatetime()
    {
        return $this->getDBManager()->processInfoById($this->getId(), 'last_update_datetime');
    }

    /**
     * @param null|string $lastUpdateDatetime
     */
    public function setLastUpdateDatetime($lastUpdateDatetime)
    {
        $this->getDBManager()->processUpdateById($this->getId(), ['last_update_datetime' => $lastUpdateDatetime]);
    }

    /**
     * @param ProcessInterface $process
     *
     * @return int process pid
     */
    public function forkProcess(ProcessInterface $process)
    {
        $pid = pcntl_fork();
        if ($pid === 0) {//child process start
            try {
//                $this->clearParentResources();
                $process->init();//all resourses have to be init again
                $process->initSignalsHandlers();
                $process->setStatus(ProcessInterface::STATUS_RUNNING);
                $process->start();

            } catch (\Exception $e) {

                $msg = '"' . $e->getMessage() . '" ' . $e->getTraceAsString();
                echo PHP_EOL . date('Y.m.d H:i:s') . ' process with id=' . $process->getId() . ' got exception ' . $msg. PHP_EOL;
                $process->errorInsertToLog(date('Y-m-d H:i:s') . '   ' . $msg);

            } finally {

                $process->setStatus(ProcessInterface::STATUS_STOPPED);
//                $process->setPid(0);
                exit(1);
            }
        }
        //parent process
//        pcntl_wait($status);

        return $pid;
    }

    /**
     * @param string $error
     */
    public function errorInsertToLog($error)
    {
        $this->getDBManager()->processErrorInsertToLog($this->getId(), $error);
    }

    /**
     * ask process to start
     *
     * @return bool
     */
    public function isStartNeeded()
    {
        $status = $this->getStatus();
        return $status === ProcessInterface::STATUS_RUN
            || (
                $status === ProcessInterface::STATUS_STOPPED
                && $this->getMode() === ProcessInterface::MODE_REPEAT
            );
    }

    /**
     * ask process to stop
     *
     * @return bool
     */
    public function isStopNeeded()
    {
        return $this->getStatus() === ProcessInterface::STATUS_STOP;
    }
}