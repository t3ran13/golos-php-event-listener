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

        return $this->connect;
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
        $this->connect();
        $id = $this->connect->incr('app:listeners:last_id');

        return $this->connect->mset(
            [
                "app:listeners:{$id}:event"   => $event,
                "app:listeners:{$id}:handler" => get_class($handler)
            ]
        );
    }

    /**
     * remove all events from listeners list
     *
     * @return void
     */
    public function clearListenersList()
    {
        $this->connect()->select(0);
        return $this->connect->del($this->connect->keys('app:listeners:*'));
    }


}