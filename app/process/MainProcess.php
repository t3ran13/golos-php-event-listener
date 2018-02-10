<?php



namespace GolosEventListener\app\process;


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

    public function start()
    {
        //init connect to db
        // get listeners list

    }
}