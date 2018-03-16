<?php



namespace GolosEventListener\app\process;


use GolosEventListener\app\AppConfig;
use GolosEventListener\app\db\DBManagerInterface;
use GrapheneNodeClient\Commands\CommandQueryData;
use GrapheneNodeClient\Connectors\WebSocket\GolosWSConnector;

class BlockchainExplorerProcess extends ProcessAbstract
{
    protected $lastBlock = 14745442;
    protected $priority = 10;
    protected $isRunning = true;

    /**
     * BlockchainExplorerProcess constructor.
     *
     * @param DBManagerInterface $DBManager
     */
    public function __construct(DBManagerInterface $DBManager)
    {
        $this->setDBManager($DBManager);
    }

    public function initSignalsHandlers()
    {
        pcntl_signal(SIGTERM, [$this, 'signalsHandlers']);
    }

    public function signalsHandlers($signo, $pid = null, $status = null)
    {
        echo PHP_EOL . ' --- process with pid=' . $this->getPid() . ' got signal=' . $signo;

        switch ($signo) {
            case SIGTERM:
                $this->isRunning = false;
                break;
            default:
        }
    }

    public function start()
    {
//        pcntl_setpriority($this->priority);
//        echo PHP_EOL . ' BlockchainExplorer is running, info '
//            . print_r($this->getDBManager()->processInfoById($this->getId()), true);
        $settings = $this->prepareSettings();
        $scanBlock = $settings['last_block'] + 1;

        $n = 1;
        while ($this->isRunning) {

            $this->setLastUpdateDatetime(date('Y:m:d H:i:s'));

            $info = $this->getDBManager()->processInfoById($this->getId());
            echo PHP_EOL . ($n++) . ' BlockchainExplorer is running, info '
                . print_r($info, true);


            $this->runBlockScanner($scanBlock);

            $this->getDBManager()->processUpdateById(
                $this->getId(),
                ['data:last_block' => $scanBlock++]
            );


            pcntl_signal_dispatch();
            sleep(3);
        }
    }

    public function runBlockScanner($blockNumber)
    {
        try {
            $connector = new GolosWSConnector();

            $commandQuery = new CommandQueryData();
            $commandQuery->setParamByKey('0', $blockNumber);//blockNum
            $commandQuery->setParamByKey('1', false);//onlyVirtual

            $command = new GetOpsInBlock($connector);
            $data = $command->execute(
                $commandQuery,
                'result'
            );

            echo PHP_EOL . ' content of block ' . $blockNumber . ': '
                . print_r($data, true);


        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function prepareSettings()
    {
        $startBlock = empty($this->lastBlock) ? 0 : $this->lastBlock;

        $info = $this->getDBManager()->processInfoById($this->getId());
        if (
            !isset($info['data']['last_block'])
            || isset($info['data']['last_block']) < $startBlock
        ) {
            $this->getDBManager()->processUpdateById(
                $this->getId(),
                ['data:last_block' => $startBlock]
            );
            $info['data']['last_block'] = $startBlock;
        }

        return $info['data'];
    }
}