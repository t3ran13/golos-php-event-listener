<?php


namespace GolosPhpEventListener\app\db;


use GolosPhpEventListener\app\handlers\HandlerInterface;
use GolosPhpEventListener\app\process\ProcessInterface;

class RedisManager implements DBManagerInterface
{
    protected $keyPrefix = 'GEL';
    protected $connect;

    public function __construct()
    {
        $this->connect();
    }


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
            $this->connect = new \Predis\Client(
                [
                    'scheme'             => 'tcp',
                    'host'               => 'redis',
                    'port'               => 6379,
                    'database'           => 0,
                    'read_write_timeout' => -1,
                    'async'              => false,
                    'password'           => getenv('REDIS_PSWD')
                ]
            );
        }

        return $this->connect;
    }

    /**
     * add new event listener
     *
     * @param int   $id
     * @param array $options
     *
     * @return void
     */
    public function listenerAdd($id, $options)
    {
        $prefix = "{$this->keyPrefix}:listeners:{$id}";
        $set = [];
        foreach ($options as $key => $val) {
            $set["{$prefix}:{$key}"] = $val;
        }

        return $this->connect->mset($set);
    }

    /**
     * remove all events from listeners list
     *
     * @return void
     */
    public function listenersListClear()
    {
        $keys = $this->connect->keys("{$this->keyPrefix}:listeners:*");
        if (!empty($keys)) {
            return $this->connect->del($keys);
        }
        return true;
    }

    /**
     * get listeners list
     *
     * @return array
     */
    public function listenersListGet()
    {
        $keys = $this->connect->keys("{$this->keyPrefix}:listeners:*");
        $values = $this->connect->mGet($keys);

        $data = [];
        foreach ($keys as $n => $keyFull) {
            $shortKey = str_replace("{$this->keyPrefix}:listeners:", '', $keyFull);
            $data = $this->setArrayElementByKey(
                $data,
                $shortKey,
                $values[$n]
            );
        }

        return $data;
    }

    /**
     * update listener data
     *
     * @param int   $id
     * @param array $options
     *
     * @return mixed
     */
    public function listenerUpdateById($id, $options)
    {
        $set = [];

        foreach ($options as $key => $val) {
            $set["{$this->keyPrefix}:listeners:{$id}:{$key}"] = $val;
        }

        return $this->connect->mset($set);
    }

    /**
     * get listener data by id
     *
     * @param int         $id
     * @param null|string $field
     *
     * @return mixed
     */
    public function listenerGetById($id, $field = null)
    {
        if ($field === null) {
            $keys = $this->connect->keys("{$this->keyPrefix}:listeners:{$id}:*");
            $values = $this->connect->mGet($keys);

            $data = [];
            foreach ($keys as $n => $keyFull) {
                $shortKey = str_replace("{$this->keyPrefix}:listeners:{$id}:", '', $keyFull);
                $data = $this->setArrayElementByKey($data, $shortKey, $values[$n]);
            }
        } else {
            $data = $this->connect->get("{$this->keyPrefix}:listeners:{$id}:" . $field);
        }

        return $data;
    }

    /**
     * @param int   $id
     * @param array $options
     *
     * @return int process id in db
     */
    public function processAdd($id, $options)
    {
        $set = [];
        foreach ($options as $key => $val) {
            $set["{$this->keyPrefix}:processes:{$id}:{$key}"] = $val;
        }

        return $this->connect->mset($set);
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
        $set = [];

        foreach ($options as $key => $val) {
            $set["{$this->keyPrefix}:processes:{$id}:{$key}"] = $val;
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
        if ($field === null) {
            $keys = $this->connect->keys("{$this->keyPrefix}:processes:{$id}:*");
            $values = $this->connect->mGet($keys);

            $data = [];
            foreach ($keys as $n => $keyFull) {
                $shortKey = str_replace("{$this->keyPrefix}:processes:{$id}:", '', $keyFull);
                $data = $this->setArrayElementByKey($data, $shortKey, $values[$n]);
            }
        } else {
            $data = $this->connect->get("{$this->keyPrefix}:processes:{$id}:" . $field);
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
        $keys = $this->connect->keys("{$this->keyPrefix}:processes:*");
        if (!empty($keys)) {
            return $this->connect->del($keys);
        }
        return true;
    }

    /**
     * remove all process from list
     *
     * @return array
     */
    public function processesListGet()
    {
        $keys = $this->connect->keys("{$this->keyPrefix}:processes:*");
        $values = $this->connect->mGet($keys);

        $data = [];
        foreach ($keys as $n => $keyFull) {
            $shortKey = str_replace("{$this->keyPrefix}:processes:", '', $keyFull);
            $data = $this->setArrayElementByKey(
                $data,
                $shortKey,
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

    /**
     * remove all events from list
     *
     * @return void
     */
    public function eventsListClear()
    {
        $keys = $this->connect->keys("{$this->keyPrefix}:events:*");
        if (!empty($keys)) {
            return $this->connect->del($keys);
        }
        return true;
    }

    /**
     * @param int   $listenerId
     * @param array $trx
     *
     * @return bool status
     */
    public function eventAdd($listenerId, $trx)
    {
        $status = $this->connect->mset(
            [
                "{$this->keyPrefix}:events:{$listenerId}:{$trx['block']}:{$trx['trx_in_block']}" => json_encode($trx, JSON_UNESCAPED_UNICODE)
            ]
        );

        return $status;
    }

    /**
     * @param int $listenerId
     * @param int $blockN
     * @param int $trxInBlock
     *
     * @return mixed status
     */
    public function eventDelete($listenerId, $blockN, $trxInBlock)
    {
        return $this->connect->del("{$this->keyPrefix}:events:{$listenerId}:{$blockN}:{$trxInBlock}");
    }

    /**
     * @param int $listenerId
     *
     * @return int
     */
    public function eventsCountByListenerId($listenerId)
    {
        $keys = $this->connect->keys("{$this->keyPrefix}:events:{$listenerId}:*");

        return count($keys);
    }

    /**
     * @param int $listenerId
     *
     * @return array as key => trx
     */
    public function eventsListByListenerId($listenerId)
    {
        $keys = $this->connect->keys("{$this->keyPrefix}:events:{$listenerId}:*");
        if (count($keys) === 0) {
            return [];
        }
        $values = $this->connect->mGet($keys);

        return array_combine($keys, $values);
    }

    /**
     * insert error to error log list
     *
     * @param int    $id
     * @param string $error
     *
     * @return mixed
     */
    public function listenerErrorInsertToLog($id, $error)
    {
        return $this->connect->rPush("{$this->keyPrefix}:listeners:{$id}:errors_list", $error);
    }

    /**
     * insert error to error log list
     *
     * @param int    $id
     * @param string $error
     *
     * @return mixed
     */
    public function processErrorInsertToLog($id, $error)
    {
        return $this->connect->rPush("{$this->keyPrefix}:processes:{$id}:errors_list", $error);
    }
}