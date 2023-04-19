<?php

namespace tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Socket\Raw\Socket;
use Yeelight\Bulb\Bulb;
use Yeelight\Bulb\BulbFactory;
use Yeelight\YeelightRawClient;

/**
 * @property YeelightRawClient      $client
 * @property MockObject|Socket      $socket
 * @property MockObject|BulbFactory $bulbFactory
 * @property int                    $readTimeout
 */
class YeelightRawClientTest extends TestCase
{
    const RESPONSE = "HTTP/1.1 200 OK
        Cache-Control: max-age=3600
        Date:
        Ext:
        Location: yeelight://192.168.1.102:55443
        Server: POSIX UPnP/1.0 YGLC/1
        id: 0x0000000000000000
        model: color
        fw_ver: 45
        support: get_prop set_default set_power toggle set_bright start_cf stop_cf set_scene cron_add cron_get cron_del set_ct_abx set_rgb set_hsv set_adjust set_music set_name
        power: on
        bright: 100
        color_mode: 2
        ct: 2926
        rgb: 5728000
        hue: 359
        sat: 100
        name:";

    const BULB = [
        'HTTP/1.1 200 OK' => 'HTTP/1.1 200 OK',
        'Cache-Control' => ' max-age=3600',
        'Date' => '',
        'Ext' => '',
        'Location' => ' yeelight://192.168.1.102:55443',
        'Server' => ' POSIX UPnP/1.0 YGLC/1',
        'id' => ' 0x0000000000000000',
        'model' => ' color',
        'fw_ver' => ' 45',
        'support' => ' get_prop set_default set_power toggle set_bright start_cf stop_cf set_scene cron_add cron_get cron_del set_ct_abx set_rgb set_hsv set_adjust set_music set_name',
        'power' => ' on',
        'bright' => ' 100',
        'color_mode' => ' 2',
        'ct' => ' 2926',
        'rgb' => ' 5728000',
        'hue' => ' 359',
        'sat' => ' 100',
        'name' => '',
    ];

    public function setUp(): void
    {
        $this->readTimeout = 2;
        $this->socket = $this->createMock(Socket::class);
        $this->bulbFactory = $this->createMock(BulbFactory::class);
        $this->client = new YeelightRawClient(
            $this->readTimeout,
            $this->socket,
            $this->bulbFactory
        );
    }

    public function test_searchForBulb_will_return_list_of_bulbs()
    {
        $this->socket
            ->expects($this->once())
            ->method('sendTo')
            ->with(
                YeelightRawClient::DISCOVERY_REQUEST,
                YeelightRawClient::NO_FLAG,
                YeelightRawClient::MULTICAST_ADDRESS
            );

        $this->socket
            ->expects($this->once())
            ->method('setBlocking');

        $this->socket
            ->expects($this->exactly(2))
            ->method('selectRead')
            ->with($this->readTimeout)
            ->willReturn(true, false);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(YeelightRawClient::PACKET_LENGTH)
            ->willReturn(self::RESPONSE);

        $bulb = $this->createMock(Bulb::class);
        $bulb
            ->expects($this->once())
            ->method('getIp')
            ->willReturn('192.168.1.102');

        $this->bulbFactory
            ->expects($this->once())
            ->method('create')
            ->with(self::BULB)
            ->willReturn($bulb);

        $bulbList = $this->client->search();

        $this->assertCount(1, $bulbList);
        $bulb = reset($bulbList);
        $this->assertInstanceOf(Bulb::class, $bulb);
    }
}
