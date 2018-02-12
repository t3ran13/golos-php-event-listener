<?php


namespace GolosEventListener\app\db;


use GolosEventListener\app\handlers\HandlerInterface;

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
    public function addListener($event, HandlerInterface $handler);

    /**
     * remove all events from listeners list
     *
     * @return void
     */
    public function clearListenersList();
}