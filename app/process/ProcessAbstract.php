<?php


namespace GolosEventListener\app\process;


use GolosEventListener\app\db\DBManagerInterface;

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
     * @param DBManagerInterface $dbManager
     */
    public function setDBManager($dbManager)
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

        if (!$pid = pcntl_fork()) {
            try {
                //child process
                $process->initSignalsHandlers();
                $process->setStatus(ProcessInterface::STATUS_RUNNING);
                $process->start();

            } catch (\Exception $e) {

                echo PHP_EOL . ' --- process with pid=' . $this->getPid() . ' got exception ' . $e->getMessage();

            } finally {

                $process->setStatus(ProcessInterface::STATUS_STOPPED);
//                $process->setPid(0);
                exit();
            }
        }
        //parent process
//        pcntl_wait($status);

        return $pid;
    }
}