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
     * @param array            $conditions
     * @param HandlerInterface $handler
     *
     * @return void
     */
    public function listenerAdd($conditions, HandlerInterface $handler);

    /**
     * remove all events from listeners list
     *
     * @return void
     */
    public function listenersListClear();

    /**
     * get listeners list
     *
     * @return array
     */
    public function listenersListGet();

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
     * @param null|string $field
     *
     * @return mixed
     */
    public function processInfoById($id, $field = null);

    /**
     * remove all process from list
     *
     * @return void
     */
    public function processesListClear();

    /**
     * get processes list
     *
     * @return array
     */
    public function processesListGet();

    /**
     * remove all events from list
     *
     * @return void
     */
    public function eventsListClear();

    /**
     * @param int   $listenerId
     * @param array $trx
     *
     * @return bool status
     */
    public function eventAdd($listenerId, $trx);
}