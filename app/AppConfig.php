<?php



namespace GolosEventListener\app;



use GolosEventListener\app\handlers\HandlerInterface;

class AppConfig
{
    protected $listeners = [];

    /**
     * @param string                 $eventKey
     * @param HandlerInterface $handler
     *
     * @return $this
     */
    public function addListener($eventKey, HandlerInterface $handler)
    {
        $this->listeners[$eventKey] = $handler;

        return $this;
    }

    /**
     *
     *
     * @return array
     */
    public function getListenersList()
    {
        return $this->listeners;
    }
}