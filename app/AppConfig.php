<?php



namespace GolosEventListener\app;



class AppConfig
{
    protected $listeners = [];

    public function addListener($eventKey, $handler)
    {
        $this->listeners[$eventKey] = $handler;

        return $this;
    }

    public function getListenersList()
    {
        return $this->listeners;
    }
}