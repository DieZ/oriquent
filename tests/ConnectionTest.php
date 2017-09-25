<?php

namespace Sgpatil\Orientdb;

use Sgpatil\Orientdb\Connection;
use Sgpatil\Orientdb\General;

class ConnectionTest extends BaseTest {

 
     public function testConnection()
    {
        $c = $this->getConnection('orientdb');

        $this->assertInstanceOf('Sgpatil\Orientdb\Connection', $c);
    }

}
