<?php


namespace GolosPhpEventListener\app;


use GolosPhpEventListener\app\handlers\HandlerInterface;

class AppConfig
{
    protected $listeners = [];

    /**
     * @param array            $conditions
     * @param HandlerInterface $handler
     *
     * @return $this
     */
    public function addListener($conditions, HandlerInterface $handler)
    {
        $this->listeners[] = [
            'conditions' => $conditions,
            'handler'    => $handler
        ];

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