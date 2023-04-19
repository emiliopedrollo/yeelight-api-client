<?php

namespace tests\Bulb;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophet;
use Socket\Raw\Factory;
use Socket\Raw\Socket;
use Yeelight\Bulb\BulbFactory;

/**
 * @property MockObject|Factory socketFactory
 * @property MockObject|Socket  socket
 * @property BulbFactory                               factory
 */
class BulbFactoryTest extends TestCase
{

    public function setUp(): void
    {
        $this->prophet = new Prophet;
        $this->socketFactory = $this->createMock(Factory::class);
        $this->socket = $this->createMock(Socket::class);
        $this->factory = new BulbFactory($this->socketFactory);
    }

    public function test_that_factory_can_create_Bulb()
    {
        $data = [
            'Location' => 'yeelight://192.168.1.239:55443',
            'id' => '0x0000000000000000',
        ];
        $this->socketFactory
            ->expects($this->once())
            ->method('createTcp4')
            ->willReturn($this->socket);

        $bulb = $this->factory->create($data);
        $this->assertEquals('192.168.1.239', $bulb->getIp());
        $this->assertEquals(55443, $bulb->getPort());
        $this->assertEquals('0x0000000000000000', $bulb->getId());
    }
}
