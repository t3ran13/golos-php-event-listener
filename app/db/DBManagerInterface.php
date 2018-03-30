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
     * @param HandlerInterface $handler
     * @param array            $options
     *
     * @return void
     */
    public function listenerAdd(HandlerInterface $handler, $options);

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
     * update listener data
     *
     * @param int   $id
     * @param array $options
     *
     * @return mixed
     */
    public function listenerUpdateById($id, $options);

    /**
     * get listener data by id
     *
     * @param int         $id
     * @param null|string $field
     *
     * @return mixed
     */
    public function listenerGetById($id, $field = null);

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

    /**
     * insert error to error log list
     *
     * @param int    $id
     * @param string $error
     *
     * @return mixed
     */
    public function listenerErrorInsertToLog($id, $error);

    /**
     * insert error to error log list
     *
     * @param int    $id
     * @param string $error
     *
     * @return mixed
     */
    public function processErrorInsertToLog($id, $error);
}