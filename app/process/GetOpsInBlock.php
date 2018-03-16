<?php


namespace GolosEventListener\app\process;


use GrapheneNodeClient\Commands\DataBase\CommandAbstract;

class GetOpsInBlock extends CommandAbstract
{
    /** @var string */
    protected $method = 'get_ops_in_block';

    /** @var array */
    protected $queryDataMap = [
        '0' => ['integer'], //blockNum
        '1' => ['bool'], //onlyVirtual
    ];
}