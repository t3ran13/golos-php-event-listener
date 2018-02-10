<?php



namespace GolosEventListener\app\process;


abstract class ProcessAbstract implements ProcessInterface
{
    public function forkProcess($childFunc)
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
        pcntl_wait($status);
    }
}