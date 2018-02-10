<?php



namespace GolosEventListener\app\process;


use GolosEventListener\app\AppConfig;
use GolosEventListener\app\db\DBManagerInterface;

class MainProcess extends ProcessAbstract
{
    /**
     * @var null|AppConfig
     */
    public $appConfig;
    /**
     * @var null|DBManagerInterface
     */
    public $db;

    /**
     * MainProcess constructor.
     *
     * @param AppConfig $appConfig
     */
    public function __construct(AppConfig $appConfig, DBManagerInterface $DBManager)
    {
        $this->appConfig = $appConfig;
        $this->db = $DBManager;
    }

    /**
     * update params and listeners list
     */
    public function init()
    {
        $listeners = $this->appConfig->getListenersList();
        $this->db->clearListenersList();
        foreach ($listeners as $event => $handler) {
            $this->db->addListener($event, $handler);
        }
    }


    public function start()
    {
        $this->init();

        //init connect to db
        // get listeners list

    }
}