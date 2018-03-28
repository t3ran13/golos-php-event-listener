<?php



namespace GolosEventListener\app\handlers;



interface HandlerInterface
{
    const MODE_REPEAT = 'repeat';

    /**
     * @return null|string
     */
    public function getListenerMode();

    /**
     * @param null|string $mode
     */
    public function setListenerMode($mode);
}