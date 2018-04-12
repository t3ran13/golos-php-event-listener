<?php



namespace GolosEventListener;


use GolosEventListener\app\handlers\HandlerAbstract;
use GolosEventListener\app\handlers\HandlerInterface;


class PostIsCreatedHandler extends HandlerAbstract
{
    protected $priority = 15;

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
        pcntl_signal(SIGHUP, [$this, 'signalsHandlers']); //restart process
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

        echo PHP_EOL . ' --- listener with pid=' . $this->getPid() . ' is running';
        sleep(1);
        echo PHP_EOL . ' --- listener with pid=' . $this->getPid() . ' did work';
    }
}