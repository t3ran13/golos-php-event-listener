<?php



namespace GolosPhpEventListener\app\process;


use GolosPhpEventListener\app\db\DBManagerInterface;

interface ProcessInterface
{
    const STATUS_RUN = 'run';
    const STATUS_RUNNING = 'running';
    const STATUS_STOP = 'stop';
    const STATUS_STOPPED = 'stopped';
    const MODE_REPEAT = 'repeat';
    const MODE_ONCE = 'once';

    /**
     * run before process start
     *
     * @return void
     */
    public function init();

    /**
     * @param DBManagerInterface $dbManager
     */
    public function setDBManager(DBManagerInterface $dbManager);

    /**
     * @return null|DBManagerInterface
     */
    public function getDBManager();

    /**
     * set id, witch it have in db
     *
     * @param string $id
     */
    public function setId($id);

    /**
     * get id, witch it have in db
     *
     * @return null|int
     */
    public function getId();

    /**
     * @param int $pid
     */
    public function setPid($pid);

    /**
     * @return null|string
     */
    public function getPid();

    /**
     * @param string $priority
     */
    public function setPriority($priority);

    /**
     * @return null|string
     */
    public function getPriority();

    /**
     * @return null|string
     */
    public function getStatus();

    /**
     * @param null|string $status
     */
    public function setStatus($status);

    /**
     * @return null|string
     */
    public function getMode();

    /**
     * @param null|string $mode
     */
    public function setMode($mode);

    /**
     * @return null|string
     */
    public function getLastUpdateDatetime();

    /**
     * @param null|string $lastUpdateDatetime
     */
    public function setLastUpdateDatetime($lastUpdateDatetime);

    public function initSignalsHandlers();

    public function start();

    /**
     * @param ProcessInterface $process
     *
     * @return int process pid
     */
    public function forkProcess(ProcessInterface $process);

    /**
     * @param string $error
     */
    public function errorInsertToLog($error);

    /**
     * @return bool
     */
    public function isStartNeeded();

    /**
     * @return bool
     */
    public function isStopNeeded();

    /**
     * clear parent resourses in child process
     *
     * @return void
     */
    public function clearLegacyResourcesInChild();
}