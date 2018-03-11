<?php



namespace GolosEventListener\app\process;


abstract class ProcessAbstract implements ProcessInterface
{
    /** @var null|string  */
    protected $pid = null;
    /** @var null|string  */
    protected $priority = 0;

    /**
     * @return null|string
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param string $pid
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * @return null|string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param string $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function forkProcess(callable $childFunc)
    {
        if (!$pid = pcntl_fork()) {
            try {
                //child process
                call_user_func($childFunc);
            } catch (\Exception $e) {
                exit();
            } finally {
                exit();
            }
        }
        //parent process
//        pcntl_wait($status);

        return $pid;
    }
}