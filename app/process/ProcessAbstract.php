<?php



namespace GolosEventListener\app\process;


use GolosEventListener\app\db\DBManagerInterface;

abstract class ProcessAbstract implements ProcessInterface
{
    /** @var null|DBManagerInterface  */
    protected $dbManager = null;
    /** @var null|int id in database*/
    protected $id = null;
    /** @var null|int  */
    protected $pid = null;
    /** @var null|string  */
    protected $priority = 0;

    /**
     * @param DBManagerInterface $dbManager
     */
    public function setDBManager($dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * @return null|DBManagerInterface
     */
    public function getDBManager()
    {
        return $this->dbManager;
    }

    /**
     * set id, witch it have in db
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * get id, witch it have in db
     *
     * @return null|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return null|int
     */
    public function getPid()
    {
        if (!$this->pid) {
            $pid = $this->getDBManager()->processInfoById($this->getId(), 'pid');
            $this->pid = $pid === false ? null : $pid;
        }

        return $this->pid;
    }

    /**
     * @param int $pid
     */
    public function setPid($pid)
    {
        $this->getDBManager()->processUpdateById($this->getId(), ['pid' => $pid]);
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

    /**
     * @param ProcessInterface $process
     *
     * @return int process pid
     */
    public function forkProcess(ProcessInterface $process)
    {
        $process->beforeStartAsFork();

        if (!$pid = pcntl_fork()) {
            try {
                //child process
                $process->start();
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

    /**
     *
     */
    public function beforeStartAsFork()
    {
        $processDBId = $this->getDBManager()->processAdd($this, ['status' => 'run']);
        $this->setId($processDBId);
    }
}