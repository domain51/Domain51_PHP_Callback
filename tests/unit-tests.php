<?php

require_once dirname(__FILE__) . '/bootstrap.php';;

class Domain51_PHP_CallbackTestSuite extends TestSuite
{
    public function __construct() 
    {
        $this->addTestFile('PHP/CallbackTest.php');
    }
}
