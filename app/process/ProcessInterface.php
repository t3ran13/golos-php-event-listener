<?php



namespace GolosEventListener\app\process;


interface ProcessInterface
{
    /**
     * @param string $pid
     */
    public function setPid($pid);

    /**
     * @return null|string
     */
    public function getPid();

    /**
     * @param string $priority
     */
    public function setPriority($priority);

    /**
     * @return null|string
     */
    public function getPriority();

    public function initSignalsHandlers();

    public function start();

    /**
     * @param callable $childFunc
     *
     * @return int process pid
     */
    public function forkProcess(callable $childFunc);
}