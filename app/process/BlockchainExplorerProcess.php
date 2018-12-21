<?php


namespace GolosPhpEventListener\app\process;



use GolosPhpEventListener\app\process\handlers\EventHandlerInterface;
use GrapheneNodeClient\Commands\CommandQueryData;
use GrapheneNodeClient\Commands\Single\GetDynamicGlobalPropertiesCommand;
use GrapheneNodeClient\Commands\Single\GetOpsInBlock;
use GrapheneNodeClient\Connectors\ConnectorInterface;
use GrapheneNodeClient\Connectors\InitConnector;
use ProcessManager\db\DBManagerInterface;
use ProcessManager\process\ProcessAbstract;
use ProcessManager\process\ProcessInterface;


class BlockchainExplorerProcess extends ProcessAbstract
{
    protected $lastBlock    = 1;
    private   $isStopSignal = false;
    private   $platform;
    /** @var EventHandlerInterface[]|ProcessInterface[]  */
    private   $events = [];
    /**
     * @var null|ConnectorInterface
     */
    protected $connector;


    /**
     * MainProcess constructor.
     *
     * @param DBManagerInterface $DBManager
     * @param string             $platform
     */
    public function __construct(DBManagerInterface $DBManager, $platform)
    {
        parent::__construct($DBManager);
        $this->platform = $platform;
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
        $this->connector = InitConnector::getConnector($this->platform);
    }

    /**
     * @param EventHandlerInterface $event
     *
     * @return $this
     */
    public function addEvent(EventHandlerInterface $event)
    {
        $this->events[] = $event;

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

    public function start()
    {
        $this->initConnector();

        $this->detectLastBlock();
        echo PHP_EOL . date('Y-m-d H:i:s') . " {$this->getProcessName()} is started from block "
            . $this->lastBlock;

        $currentBlockNumber = $this->getCurrentBlockNInChain();

        while (!$this->isStopNeeded() && !$this->isStopSignal) {
            $this->setLastUpdateDatetime(date('Y-m-d H:i:s'))
                ->saveState();
            //if last block = current, then wait 1 second, update curretn block and try again
            if ($this->lastBlock + 1 > $currentBlockNumber) {
                sleep(1);
                $currentBlockNumber = $this->getCurrentBlockNInChain();
                continue;
            }

            if (($this->lastBlock % 1000) === 0) {
                echo PHP_EOL . date('Y-m-d H:i:s') . ' BlockchainExplorer scanned block '
                    . print_r($this->lastBlock, true);
            }

            $scanBlock = $this->lastBlock + 1;
            $this->runBlockScanner($scanBlock);

            $this->getDBManager()->updProcessStateById(
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

            $totalEvents = 0;


            if (is_array($data['result'])) {
                foreach ($data['result'] as $trx) {
                    foreach ($this->events as $event) {
                        if ($event->isTrxSatisfiesConditions($trx)) {
                            $event->addEvent($trx);
                            $event->saveState();
                            $totalEvents++;
                        }
                    }
                }
            }

            if ($totalEvents > 0) {
//                echo PHP_EOL . date('Y-m-d H:i:s') . " {$this->getProcessName()} catch {$totalEvents} events in block {$blockNumber}";
            }


        } catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * get last block from DB state or from $this->lastBlock.
     * The higher value will be choosen
     */
    public function detectLastBlock()
    {
        $info = $this->getDBManager()->getProcessStateById($this->getId());
        if (
            !isset($info['data']['last_block'])
            || (integer)$info['data']['last_block'] < $this->lastBlock
        ) {
            $this->getDBManager()->updProcessStateById(
                $this->getId(),
                ['data:last_block' => $this->lastBlock]
            );
        } else {
            $this->lastBlock = (integer)$info['data']['last_block'];
        }
    }

    /**
     * @param int $blockN
     *
     * @return $this
     */
    public function setLastBlock(int $blockN)
    {
        $this->lastBlock = $blockN;

        return $this;
    }

    /**
     * get current block number from blockchain
     *
     * @return mixed
     * @throws \Exception
     */
    public function getCurrentBlockNInChain()
    {
        try {
            $commandQuery = new CommandQueryData();
            $command = new GetDynamicGlobalPropertiesCommand($this->getConnector());
            $data = $command->execute(
                $commandQuery
            );
            if (!isset($data['result'])) {
                throw new \Exception(' - got wrong answer from API for process ' . $this->getId());
            }

            $currentBlockNumber = $data['result']['head_block_number'];

        } catch (\Exception $e) {
            throw $e;
        }

        return $currentBlockNumber;
    }

}