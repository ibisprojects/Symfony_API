<?php
/**
 * Created by PhpStorm.
 * User: REEA
 * Date: 04/01/2018
 * Time: 17:13
 */

namespace API\AuthorizedGrantBundle\Monolog;


class MobileLogger
{
    public $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }
}
