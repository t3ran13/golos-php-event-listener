<?php



namespace GolosEventListener;


use GolosEventListener\app\handlers\HandlerAbstract;
use GolosEventListener\app\handlers\HandlerInterface;
use GolosEventListener\app\process\ProcessInterface;


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

        $listenerId = $this->getId();
        echo PHP_EOL . ' --- listener with id/pid=' . $listenerId . '/' . $this->getPid() . ' is running';
        $events = $this->getDBManager()->eventsListByListenerId($listenerId);
        echo PHP_EOL . ' --- listener with id=' . $listenerId . ' have total events=' . count($events);

        foreach ($events as $key => $event) {
            $ids = str_replace("app:events:{$listenerId}:",'', $key);
            list($blockN, $trxInBlock) = explode(':', $ids);
            $this->getDBManager()->eventDelete($listenerId, $blockN, $trxInBlock);
            echo PHP_EOL . ' --- listener with id=' . $listenerId . ' handle and deleted event with key=' . $key;
        }

        echo PHP_EOL . ' --- listener with id/pid=' . $listenerId . '/' . $this->getPid() . ' did work';
    }

    /**
     * ask process to start
     *
     * @return bool
     */
    public function isStartNeeded()
    {
        return $this->getStatus() === ProcessInterface::STATUS_RUN
            && $this->getDBManager()->eventsCountByListenerId($this->getId()) > 0;
    }
}