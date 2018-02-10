<?php



namespace GolosEventListener\app\db;


use GolosEventListener\app\handlers\HandlerInterface;

class RedisManager implements DBManagerInterface
{
    public function connect()
    {
        // TODO: Implement connect() method.
    }

    /**
     * add new event listener
     *
     * @param string           $event
     * @param HandlerInterface $handler
     *
     * @return void
     */
    public function addListener($event, HandlerInterface $handler)
    {
        // TODO: Implement addListener() method.
    }

    /**
     * remove event from listeners list
     *
     * @param string $event
     *
     * @return void
     */
    public function removeListener($event)
    {
        // TODO: Implement removeListener() method.
    }

    /**
     * remove all events from listeners list
     *
     * @return void
     */
    public function clearListenersList()
    {
        // TODO: Implement clearListenersList() method.
    }


}