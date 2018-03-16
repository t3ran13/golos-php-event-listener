<?php


namespace GolosEventListener\app\db;


use GolosEventListener\app\handlers\HandlerInterface;
use GolosEventListener\app\process\ProcessInterface;

class RedisManager implements DBManagerInterface
{
    protected $connect;

    public function connect()
    {
//        if ($this->connect === null) {
//            $this->connect = new \Redis();
//            $this->connect->pconnect('redis', 6379);
//            $this->connect->auth(getenv('REDIS_PSWD'));
//            $this->connect->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
//        }
//        if ($this->connect->ping() !== '+PONG') {
//            $this->connect->pconnect('redis', 6379);
//            $this->connect->auth(getenv('REDIS_PSWD'));
//            $this->connect->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
//        }

        if ($this->connect === null) {
            $this->connect = new \Predis\Client('tcp://redis:6379',
                [
                    'parameters' => [
                        'password' => getenv('REDIS_PSWD')
                    ],
                ]
            );
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
                    ? $options['last_update_datetime'] : '',
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

        foreach($options as $key => $val) {
            $set["app:processes:{$id}:{$key}"] = $val;
        }

        return $this->connect->mset($set);
    }

    /**
     * get process data by id
     *
     * @param int         $id
     * @param null|string $field
     *
     * @return mixed
     */
    public function processInfoById($id, $field = null)
    {
        $this->connect()->select(0);

        if ($field === null) {
            $keys = $this->connect->keys("app:processes:{$id}:*");
            $values = $this->connect->mGet($keys);

            $data = [];
            foreach ($keys as $n => $keyFull) {
                $shortKey = str_replace("app:processes:{$id}:", '', $keyFull);
                $data = $this->setArrayElementByKey($data, $shortKey, $values[$n]);
            }
        } else {
            $data = $this->connect->get("app:processes:{$id}:" . $field);
        }

        return $data;
    }

    /**
     * remove all process from list
     *
     * @return void
     */
    public function processesListClear()
    {
        $this->connect()->select(0);
        return $this->connect->del($this->connect->keys('app:processes:*'));
    }

    /**
     * remove all process from list
     *
     * @return array
     */
    public function processesListGet()
    {
        $this->connect()->select(0);
        $keys = $this->connect->keys("app:processes:*");
        $values = $this->connect->mGet($keys);

        $data = [];
        foreach ($keys as $n => $keyFull) {
            if ($keyFull === 'app:processes:last_id') {
                continue;
            }
            $shortKey = str_replace("app:processes:", '', $keyFull);
            list($processId, $fieldName) = explode(':', $shortKey);
            $data[$processId] = $this->setArrayElementByKey(
                isset($data[$processId]) ? $data[$processId] : [],
                $fieldName,
                $values[$n]
            );
        }

        return $data;
    }



    /**
     * get all values or vulue by key
     *
     * $getKey example: 'key:123:array' => $_SESSION['key']['123']['array']
     *
     * @param null|string $getKey
     * @param null|mixed  $default
     * @param array       $array
     *
     * @return mixed
     */
    public static function getArrayElementByKey($array = [], $getKey = null, $default = null)
    {
        $data = $array;
        if ($getKey) {
            $keyParts = explode(':', $getKey);
            foreach ($keyParts as $key) {
                if (isset($data[$key])) {
                    $data = $data[$key];
                } else {
                    $data = null;
                    break;
                }
            }
        }

        if ($data === null) {
            $data = $default;
        }

        return $data;
    }


    /**
     * set value in array by key
     *
     * $setKey example: 'key:123:array' => $_SESSION['key']['123']['array']
     *
     * @param array  $array
     * @param string $setKey
     * @param mixed  $setVal
     *
     * @return array
     */
    public static function setArrayElementByKey($array, $setKey, $setVal)
    {
        $link = &$array;
        $keyParts = explode(':', $setKey);
        foreach ($keyParts as $key) {
            if (!isset($link[$key])) {
                $link[$key] = [];
            }
            $link = &$link[$key];
        }
        $link = $setVal;

        return $array;
    }

}