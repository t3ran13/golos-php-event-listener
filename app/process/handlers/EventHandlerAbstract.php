<?php


namespace GolosPhpEventListener\app\process\handlers;


use ProcessManager\process\ProcessAbstract;

abstract class EventHandlerAbstract extends ProcessAbstract implements EventHandlerInterface
{
    /**
     * Conditions for event handling
     *
     * @var array
     */
    private $conditions   = [];
    private $isStopSignal = false;

    /**
     * Adding new condition for event handling
     *
     * @param string $trxKey
     * @param mixed  $val
     *
     * @return $this
     */
    public function addCondition(string $trxKey, $val)
    {
        $this->conditions[$trxKey] = $val;

        return $this;
    }

    /**
     * Checking transaction for fulfillment conditions
     *
     * @param array $trx
     *
     * @return bool
     */
    public function isTrxSatisfiesConditions($trx): bool
    {
        $answer = true;
        foreach ($this->conditions as $cKey => $cVal) {
            if ($cVal !== $this->getArrayElementByKey($trx, $cKey)) {
                $answer = false;
                break;
            }
        }

        return $answer;
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
     * save events to handling list
     *
     * @param array $tx
     *
     * @return $this
     */
    public function addEvent($tx)
    {
        $txJson = json_encode($tx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->objState['data']['events'][] = $txJson;

        return $this;
    }

    /**
     * get events from handling list
     *
     * @return array
     */
    public function getEvents()
    {
        $events = $this->getDataByKey('events', []);
        foreach ($events as $key => $event) {
            $events[$key] = json_decode($event, true);
        }

        return $events;
    }

    /**
     * remove event from handling list
     *
     * @param int $key
     *
     * @return $this
     */
    public function removeEventByKey(int $key)
    {
        unset($this->objState['data']['events'][$key]);

        return $this;
    }

    public function initSignalsHandlers()
    {
        pcntl_signal(SIGTERM, [$this, 'signalsHandlers']); //kill
        pcntl_signal(SIGINT, [$this, 'signalsHandlers']); //ctrl+c
        pcntl_signal(SIGHUP, [$this, 'signalsHandlers']); //restart process
    }

    public function signalsHandlers($signo, $signinfo)
    {
        echo PHP_EOL . date('Y.m.d H:i:s') . ' process ' . $this->getProcessName() . ' got signal=' . $signo . ' and signinfo='
            . print_r($signinfo, true);

        switch ($signo) {
            case SIGINT:
            case SIGTERM:
            case SIGHUP:
                echo PHP_EOL . date('Y.m.d H:i:s') . ' process \'' . $this->getProcessName() . '\' ARE TERMINATED';
                $this->isStopSignal = true;
                break;
            default:
        }
    }
}