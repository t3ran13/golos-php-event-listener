<?php


namespace GolosEventListener\app\db;


use GolosEventListener\app\handlers\HandlerInterface;
use GolosEventListener\app\process\ProcessInterface;

interface DBManagerInterface
{
    public function connect();

    /**
     * add new event listener
     *
     * @param string           $event
     * @param HandlerInterface $handler
     *
     * @return void
     */
    public function listenerAdd($event, HandlerInterface $handler);

    /**
     * remove all events from listeners list
     *
     * @return void
     */
    public function listenersListClear();

    /**
     * @param ProcessInterface $process
     * @param array            $options
     *
     * @return int process id in db
     */
    public function processAdd(ProcessInterface $process, $options);

    /**
     * update process data
     *
     * @param int   $id
     * @param array $options
     *
     * @return mixed
     */
    public function processUpdateById($id, $options);

    /**
     * get process data by id
     *
     * @param int         $id
     * @param null|string $key
     *
     * @return mixed
     */
    public function processInfoById($id, $key = null);
}