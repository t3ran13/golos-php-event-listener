<?php



namespace GolosEventListener\app\process;


use GolosEventListener\app\AppConfig;
use GolosEventListener\app\db\DBManagerInterface;

class BlockchainExplorerProcess extends ProcessAbstract
{
    protected $priority = 10;

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
        // TODO: Implement initSignalsHandlers() method.
    }

    public function start()
    {
//        pcntl_setpriority($this->priority);
        echo PHP_EOL . ' BlockchainExplorer is running, info '
            . print_r($this->getDBManager()->processInfoById($this->getId()), true);
        $this->getDBManager()->processUpdateById($this->getId(), ['status' => 'running']);

        $n = 1;
        while (true) {

            echo PHP_EOL . ($n++) . ' BlockchainExplorer is running, info '
                . print_r($this->getDBManager()->processInfoById($this->getId()), true);
            if ($n >= 10) {
                break;
            }

            $this->getDBManager()->processUpdateById($this->getId(), ['last_update_datetime' => date('Y:m:d H:i:s')]);
            sleep(1);
        }
    }
}