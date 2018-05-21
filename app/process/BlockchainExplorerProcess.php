<?php



namespace GolosPhpEventListener\app\process;



use GolosPhpEventListener\app\db\DBManagerInterface;
use GrapheneNodeClient\Commands\CommandQueryData;
use GrapheneNodeClient\Commands\Single\GetDynamicGlobalPropertiesCommand;
use GrapheneNodeClient\Commands\Single\GetOpsInBlock;
use GrapheneNodeClient\Connectors\ConnectorInterface;

class BlockchainExplorerProcess extends ProcessAbstract
{
    protected $lastBlock = 1;
    protected $priority = 10;
    protected $isRunning = true;
    protected $dbManagerClassName;
    protected $connectorClassName;
    /**
     * @var null|ConnectorInterface
     */
    protected $connector;


    /**
     * MainProcess constructor.
     *
     * @param string $dbManagerClassName
     * @param string $connectorClassName
     */
    public function __construct($dbManagerClassName = null, $connectorClassName = null)
    {
        parent::__construct();
        $this->dbManagerClassName = $dbManagerClassName === null
            ? 'GolosPhpEventListener\app\db\RedisManager' : $dbManagerClassName;
        $this->connectorClassName = $connectorClassName === null
            ? 'GrapheneNodeClient\Connectors\WebSocket\GolosWSConnector' : $connectorClassName;
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

    /**
     * @return ConnectorInterface|null
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     *
     */
    public function initConnector()
    {
        $this->connector = new $this->connectorClassName();
    }

    public function initSignalsHandlers()
    {
        pcntl_signal(SIGTERM, [$this, 'signalsHandlers']);
    }

    public function signalsHandlers($signo, $signinfo)
    {
        echo PHP_EOL . ' --- process with id=' . $this->getId() . ' got signal=' . $signo . ' and signinfo='
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

        $this->initLastBlock();
        echo PHP_EOL . date('Y-m-d H:i:s') . ' BlockchainExplorer is started from block ' . $this->lastBlock;

        $this->initConnector();
        $currentBlockNumber = $this->getCurrentBlockNumber();

        while ($this->isRunning) {
            //if last block = current, then wait 1 second, update curretn block and try again
            if ($this->lastBlock + 1 > $currentBlockNumber) {
                sleep(1);
                $currentBlockNumber = $this->getCurrentBlockNumber();
                continue;
            }

            $this->setLastUpdateDatetime(date('Y-m-d H:i:s'));

//            echo PHP_EOL . ' scan block '
//                . print_r($this->lastBlock + 1, true);


            $scanBlock = $this->lastBlock + 1;
            $this->runBlockScanner($scanBlock);

            $this->getDBManager()->processUpdateById(
                $this->getId(),
                ['data:last_block' => $scanBlock]
            );
            $this->lastBlock = $scanBlock;


            pcntl_signal_dispatch();
        }
    }

    public function runBlockScanner($blockNumber)
    {
        try {
            $listeners = $this->getDBManager()->listenersListGet();

            $commandQuery = new CommandQueryData();
            $commandQuery->setParamByKey('0', $blockNumber);//blockNum
            $commandQuery->setParamByKey('1', false);//onlyVirtual

            $command = new GetOpsInBlock($this->getConnector());
            $data = $command->execute(
                $commandQuery
            );
            if (!isset($data['result'])) {
                throw new \Exception(' - got wrong answer for block ' . $blockNumber);
            }
            $saveForHandle = [];
            if (is_array($data['result'])) {
                foreach ($data['result'] as $trx) {
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


            $totalEvents = count($saveForHandle);
            if ($totalEvents > 0) {
                echo PHP_EOL . date('Y-m-d H:i:s') . " BlockchainExplorer catch {$totalEvents} events in block {$blockNumber}";
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
            || (integer)$info['data']['last_block'] < $this->lastBlock
        ) {
            $this->getDBManager()->processUpdateById(
                $this->getId(),
                ['data:last_block' => $this->lastBlock]
            );
        } else {
            $this->lastBlock = (integer)$info['data']['last_block'];
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

    public function getCurrentBlockNumber()
    {
        try {
            $commandQuery = new CommandQueryData();
            $command = new GetDynamicGlobalPropertiesCommand($this->getConnector());
            $data = $command->execute(
                $commandQuery,
                'result'
            );

            $currentBlockNumber = $data['head_block_number'];

        } catch (\Exception $e) {
            throw $e;
        }

        return $currentBlockNumber;
    }

}