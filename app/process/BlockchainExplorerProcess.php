<?php



namespace GolosEventListener\app\process;


use GolosEventListener\app\AppConfig;
use GolosEventListener\app\db\DBManagerInterface;

class BlockchainExplorerProcess extends ProcessAbstract
{
    protected $priority = 10;

    public function initSignalsHandlers()
    {
        // TODO: Implement initSignalsHandlers() method.
    }

    public function start()
    {
//        pcntl_setpriority($this->priority);

        $n = 1;
        while (true) {
            echo PHP_EOL . ($n++) . ' BlockchainExplorer is running,  pid ' . $this->getPid();
            if ($n >= 10) {
                break;
            }
            sleep(1);
        }
    }
}