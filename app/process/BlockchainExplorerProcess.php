<?php



namespace GolosEventListener\app\process;


use GolosEventListener\app\AppConfig;
use GolosEventListener\app\db\DBManagerInterface;

class BlockchainExplorerProcess extends ProcessAbstract
{
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


        $n = 1;
        while ($this->isRunning) {

            $this->setLastUpdateDatetime(date('Y:m:d H:i:s'));

            $info = $this->getDBManager()->processInfoById($this->getId());
            echo PHP_EOL . ($n++) . ' BlockchainExplorer is running, info '
                . print_r($info, true);


            pcntl_signal_dispatch();
            sleep(1);
        }
    }
}