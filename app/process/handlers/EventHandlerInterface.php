<?php



namespace GolosPhpEventListener\app\process\handlers;



interface EventHandlerInterface
{
    /**
     * Adding new condition for event handling
     *
     * @param string $trxKey
     * @param mixed  $val
     *
     * @return $this
     */
    public function addCondition(string $trxKey, $val);

    /**
     * Checking transaction for fulfillment conditions
     *
     * @param array $trx
     *
     * @return bool
     */
    public function isTrxSatisfiesConditions($trx): bool;

    /**
     * save events to handling list
     *
     * @param array $tx
     *
     * @return $this
     */
    public function addEvent($tx);

    /**
     * get events from handling list
     *
     * @return array
     */
    public function getEvents();

    /**
     * remove event from handling list
     *
     * @param int $key
     *
     * @return $this
     */
    public function removeEventByKey(int $key);
}