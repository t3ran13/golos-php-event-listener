<?php



namespace GolosEventListener;


use GolosEventListener\app\handlers\HandlerAbstract;
use GolosEventListener\app\handlers\HandlerInterface;


class PostIsCreatedHandler extends HandlerAbstract
{
    public $listenerMode = HandlerInterface::MODE_REPEAT;

    /**
     * run before process start
     *
     * @return void
     */
    public function init()
    {
        $this->setDBManager(new RedisManager());
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
        echo PHP_EOL . ' --- ' . get_class($this) . ' is running';
        echo PHP_EOL . ' --- ' . get_class($this) . ' done work';
    }
}