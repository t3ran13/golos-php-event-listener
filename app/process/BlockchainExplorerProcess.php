<?php



namespace GolosPhpEventListener\app\process;



use GolosPhpEventListener\app\db\DBManagerInterface;
use GrapheneNodeClient\Commands\CommandQueryData;
use GrapheneNodeClient\Commands\Single\GetOpsInBlock;
use GrapheneNodeClient\Connectors\WebSocket\GolosWSConnector;

class BlockchainExplorerProcess extends ProcessAbstract
{
    protected $lastBlock = 14745442;
    protected $priority = 10;
    protected $isRunning = true;
    protected $dbManagerClassName;


    /**
     * MainProcess constructor.
     *
     * @param string $dbManagerClassName
     */
    public function __construct($dbManagerClassName = null)
    {
        $this->dbManagerClassName = $dbManagerClassName === null
            ? 'GolosPhpEventListener\app\db\RedisManager' : $dbManagerClassName;
    }

    /**
     * run before process start
     *
     * @return void
     */
    public function init()
    {
        $this->setDBManager(new $this->dbManagerClassName());
    }

    public function initSignalsHandlers()
    {
        pcntl_signal(SIGTERM, [$this, 'signalsHandlers']);
    }

    public function signalsHandlers($signo, $signinfo)
    {
        echo PHP_EOL . ' --- process with pid=' . $this->getPid() . ' got signal=' . $signo . ' and signinfo='
            . print_r($signinfo, true);

        switch ($signo) {
            case SIGTERM:
                $this->isRunning = false;
                break;
            default:
        }
    }

    public function start()
    {
        pcntl_setpriority($this->priority, getmypid());

//        pcntl_setpriority($this->priority);
//        echo PHP_EOL . ' BlockchainExplorer is running, info '
//            . print_r($this->getDBManager()->processInfoById($this->getId()), true);

        $this->initLastBlock();

        $n = 1;
        while ($this->isRunning) {

            $this->setLastUpdateDatetime(date('Y.m.d H:i:s'));

            $info = $this->getDBManager()->processInfoById($this->getId());
            echo PHP_EOL . ($n++) . ' BlockchainExplorer is running, info '
                . print_r($info, true);


            $scanBlock = $this->lastBlock + 1;
            $this->runBlockScanner($scanBlock);

            $this->getDBManager()->processUpdateById(
                $this->getId(),
                ['data:last_block' => $scanBlock]
            );
            $this->lastBlock = $scanBlock;


            pcntl_signal_dispatch();
            sleep(3);
        }
    }

    public function runBlockScanner($blockNumber)
    {
        try {
            $listeners = $this->getDBManager()->listenersListGet();

            $connector = new GolosWSConnector();

            $commandQuery = new CommandQueryData();
            $commandQuery->setParamByKey('0', $blockNumber);//blockNum
            $commandQuery->setParamByKey('1', false);//onlyVirtual

            $command = new GetOpsInBlock($connector);
            $data = $command->execute(
                $commandQuery,
                'result'
            );

            $saveForHandle = [];
            if (is_array($data)) {
                foreach ($data as $trx) {
                    foreach ($listeners as $listenerId => $listener) {
                        if ($this->isTrxSatisfiesConditions($trx, $listener['conditions'])) {
                            $saveForHandle[$listenerId][] = $trx;
                        }
                    }
                }
            }

            //echo PHP_EOL . ' found events ' . count($saveForHandle);

            foreach ($saveForHandle as $listenerId => $trxs) {
                foreach ($trxs as $trx) {
                    $this->getDBManager()->eventAdd($listenerId, $trx);
                }
            }


//            echo '<pre>' . print_r($saveForHandle, true) . '<pre>'; die; //FIXME delete it


        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function initLastBlock()
    {
        $info = $this->getDBManager()->processInfoById($this->getId());
        if (
            !isset($info['data']['last_block'])
            || isset($info['data']['last_block']) < $this->lastBlock
        ) {
            $this->getDBManager()->processUpdateById(
                $this->getId(),
                ['data:last_block' => $this->lastBlock]
            );
        } else {
            $this->lastBlock = $info['data']['last_block'];
        }
    }

    /**
     * @param int $blockN
     */
    public function setLastBlock($blockN)
    {
        $this->lastBlock = $blockN;
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

    public function isTrxSatisfiesConditions($trx, $conditions)
    {
        $answer = true;
        foreach ($conditions as $condition) {
            if ($condition['value'] !== $this->getArrayElementByKey($trx, $condition['key'])) {
                $answer = false;
                break;
            }
        }

        return $answer;
    }

    /**
     * clear parent resourses in child process
     *
     * @return void
     */
    public function clearParentResources()
    {
    }

}