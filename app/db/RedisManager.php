<?php


namespace GolosEventListener\app\db;


use GolosEventListener\app\handlers\HandlerInterface;
use GolosEventListener\app\process\ProcessInterface;

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
    public function listenerAdd($event, HandlerInterface $handler)
    {
        $this->connect()->select(0);
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
    public function listenersListClear()
    {
        $this->connect()->select(0);
        return $this->connect->del($this->connect->keys('app:listeners:*'));
    }

    /**
     * @param ProcessInterface $process
     * @param array            $options
     *
     * @return int process id in db
     */
    public function processAdd(ProcessInterface $process, $options)
    {
        $this->connect()->select(0);
        $id = $this->connect->incr('app:processes:last_id');

        $status = $this->connect->mset(
            [
                "app:processes:{$id}:last_update_datetime" => isset($options['last_update_datetime'])
                    ? $options['last_update_datetime'] : date('Y:m:d H:i:s'),
                "app:processes:{$id}:status"               => isset($options['status']) ? $options['status'] : '',
                "app:processes:{$id}:pid"                  => isset($options['pid']) ? $options['pid'] : 0,
                "app:processes:{$id}:handler"              => get_class($process)
            ]
        );

        return $status ? $id : null;
    }

    /**
     * update process data
     *
     * @param int   $id
     * @param array $options
     *
     * @return mixed
     */
    public function processUpdateById($id, $options)
    {
        $this->connect()->select(0);
        $set = [];

        if (isset($options['last_update_datetime'])) {
            $set["app:processes:{$id}:last_update_datetime"] = $options['last_update_datetime'];
        }
        if (isset($options['status'])) {
            $set["app:processes:{$id}:status"] = $options['status'];
        }
        if (isset($options['pid'])) {
            $set["app:processes:{$id}:pid"] = $options['pid'];
        }

        return $this->connect->mset($set);
    }

    /**
     * get process data by id
     *
     * @param int         $id
     * @param null|string $key
     *
     * @return mixed
     */
    public function processInfoById($id, $key = null)
    {
        $this->connect()->select(0);

        if ($key === null) {
            $keys = $this->connect->keys("app:processes:{$id}:*");
            $values = $this->connect->mGet($keys);

            $data = [];
            foreach ($keys as $n => $keyFull) {
                $shortKey = str_replace("app:processes:{$id}:", '', $keyFull);
                $data[$shortKey] = $values[$n];
            }
        } else {
            $data = $this->connect->get("app:processes:{$id}:" . $key);
        }

        return $data;
    }


}