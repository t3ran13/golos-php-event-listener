<?php



namespace GolosEventListener\app\process;


interface ProcessInterface
{
    /**
     * @param DBManagerInterface $dbManager
     */
    public function setDBManager($dbManager);

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

    public function initSignalsHandlers();

    public function start();

    /**
     * @param ProcessInterface $process
     *
     * @return int process pid
     */
    public function forkProcess(ProcessInterface $process);

    /**
     * @return void
     */
    public function beforeStartAsFork();
}