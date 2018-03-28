<?php


namespace GolosEventListener\app\handlers;


use GolosEventListener\app\db\DBManagerInterface;
use GolosEventListener\app\process\ProcessInterface;

abstract class HandlerAbstract implements HandlerInterface,ProcessInterface
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
        return $this->getDBManager()->listenerGetById($this->getId(), 'pid');
    }

    /**
     * @param int|null $pid
     */
    public function setPid($pid)
    {
        $this->getDBManager()->listenerUpdateById($this->getId(), ['pid' => $pid]);
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
        return $this->getDBManager()->listenerGetById($this->getId(), 'status');
    }

    /**
     * @param null|string $status
     */
    public function setStatus($status)
    {
        $this->getDBManager()->listenerUpdateById($this->getId(), ['status' => $status]);
    }

    /**
     * @return null|string
     */
    public function getLastUpdateDatetime()
    {
        return $this->getDBManager()->listenerGetById($this->getId(), 'last_update_datetime');
    }

    /**
     * @param null|string $lastUpdateDatetime
     */
    public function setLastUpdateDatetime($lastUpdateDatetime)
    {
        $this->getDBManager()->listenerUpdateById($this->getId(), ['last_update_datetime' => $lastUpdateDatetime]);
    }

    /**
     * @param ProcessInterface $process
     *
     * @return int process pid
     */
    public function forkProcess(ProcessInterface $process)
    {
    }

    /**
     * @return null|string
     */
    public function getListenerMode()
    {
        return $this->getDBManager()->listenerGetById($this->getId(), 'data:mode');
    }

    /**
     * @param null|string $mode
     */
    public function setListenerMode($mode)
    {
        $this->getDBManager()->listenerUpdateById($this->getId(), ['data:mode' => $mode]);
    }
}