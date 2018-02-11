<?php



namespace GolosEventListener\app\db;


use GolosEventListener\app\handlers\HandlerInterface;

class RedisManager implements DBManagerInterface
{
    protected $connect;

    public function connect()
    {
        if ($this->connect === null || $this->connect->ping() !== '+PONG') {
            $this->connect = new \Redis();
            $this->connect->pconnect('redis', 6379);
            $this->connect->auth(getenv('REDIS_PSWD'));
        }
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
        $this->connect();
        $this->connect();
        // TODO: Implement clearListenersList() method.
    }


}