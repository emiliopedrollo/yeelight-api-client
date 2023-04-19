<?php

namespace tests\Bulb;

use PHPUnit\Framework\MockObject\MockObject;
use React\Promise\RejectedPromise;
use Socket\Raw\Socket;
use Yeelight\Bulb\Bulb;
use Yeelight\Bulb\BulbProperties;
use Yeelight\Bulb\Response;

/**
 * @property Bulb              bulb
 * @property MockObject|Socket socket
 */
class BulbTest extends \PHPUnit\Framework\TestCase
{
    const SUCCESS_RESPONSE = "{\"id\": 0, \"result\":{}}\r\n";
    const ERROR_RESPONSE = "{\"id\": 0, \"error\":{\"code\":-5000,\"message\":\"general error\"}}\r\n";
    const NOTIFICATION_RESPONSE = "{\"method\": \"props\", \"params\":{}}\r\n";


    public function setUp(): void
    {
        $this->socket = $this->createMock(Socket::class);
        $this->bulb = new Bulb($this->socket, '192.168.1.2', 55443, '0x0');
    }

    public function test_getProp()
    {
        $properties = [BulbProperties::BRIGHT, BulbProperties::SATURATION, 'foo'];
        $response = ['id' => 1, 'result' => [100, 100, '']];
        $expected = new Response($response);

        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'get_prop', 'params' => $properties
                ]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(json_encode($response));

        $result = $this->bulb->getProp($properties);
        $result->done(function (Response $result) use ($expected) {
            $this->assertEquals($expected, $result);
            $this->assertTrue($result->isSuccess());
        });
    }

    public function test_setCtAbx()
    {
        $ctValue = 1700;
        $effect = Bulb::EFFECT_SMOOTH;
        $duration = 30;
        $buffer = json_encode(
            ['id' => hexdec($this->bulb->getId()), 'method' => 'set_ct_abx', 'params' => [
                $ctValue, $effect, $duration
            ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->setCtAbx($ctValue, $effect, $duration);
    }

    public function test_setCtAbx_can_handle_error_from_server()
    {
        $ctValue = -100;
        $effect = 'foo';
        $duration = 10;
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'set_ct_abx', 'params' => [
                    $ctValue, $effect, $duration
                ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::ERROR_RESPONSE);

        $response = $this->bulb->setCtAbx($ctValue, $effect, $duration);
        $response->done(function (Response $result) {
            $this->assertFalse($result->isSuccess());
        });
    }


    public function test_setCtAbx_can_handle_notification_from_server()
    {
        $ctValue = -100;
        $effect = 'foo';
        $duration = 10;
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'set_ct_abx', 'params' => [
                    $ctValue, $effect, $duration
                ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE.self::NOTIFICATION_RESPONSE);

        $response = $this->bulb->setCtAbx($ctValue, $effect, $duration);

        $response->done(
            function (Response $result) {
                $this->assertTrue($result->isSuccess());
            }
        );
    }


    public function test_setCtAbx_can_handle_notification_before_response_from_server()
    {
        $ctValue = -100;
        $effect = 'foo';
        $duration = 10;
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'set_ct_abx', 'params' => [
                    $ctValue, $effect, $duration
                ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->exactly(2))
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::NOTIFICATION_RESPONSE,self::SUCCESS_RESPONSE);

        $response = $this->bulb->setCtAbx($ctValue, $effect, $duration);

        $response->done(
            function (Response $result) {
                $this->assertTrue($result->isSuccess());
            }
        );
    }

    public function test_setRgb()
    {
        $rgbValue =  0;
        $effect = Bulb::EFFECT_SUDDEN;
        $duration = 1000;
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'set_rgb', 'params' => [
                    $rgbValue, $effect, $duration
                ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->setRgb($rgbValue, $effect, $duration);
    }

    public function test_setHsv()
    {
        $hue = 0;
        $sat = 0;
        $effect = Bulb::EFFECT_SMOOTH;
        $duration = 1000;
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'set_hsv', 'params' => [
                    $hue, $sat, $effect, $duration
                ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->setHsv($hue, $sat, $effect, $duration);
    }

    public function test_setBright()
    {
        $brightness = 50;
        $effect = Bulb::EFFECT_SMOOTH;
        $duration = 1000;
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'set_bright', 'params' => [
                    $brightness, $effect, $duration
                ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->setBright($brightness, $effect, $duration);
    }

    public function test_setPower()
    {
        $power = Bulb::ON;
        $effect = Bulb::EFFECT_SMOOTH;
        $duration = 1000;
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'set_power', 'params' => [
                    $power, $effect, $duration
                ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->setPower($power, $effect, $duration);
    }

    public function test_toggle()
    {
        $buffer = json_encode(['id' => hexdec($this->bulb->getId()), 'method' => 'toggle', 'params' => []])."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->toggle();
    }

    public function test_setDefault()
    {
        $buffer = json_encode(['id' => hexdec($this->bulb->getId()), 'method' => 'set_default', 'params' => []])."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->setDefault();
    }

    public function test_startCf()
    {
        $count = 2;
        $action = Bulb::ACTION_BEFORE;
        $flowExpression = [
            [1000, 2, 2700, 100],
            [500, 1, 255, 10],
        ];
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'start_cf', 'params' => [
                    $count, $action, '1000,2,2700,100,500,1,255,10'
                ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->startCf($count, $action, $flowExpression);
    }

    public function test_stopCf()
    {
        $buffer = json_encode(['id' => hexdec($this->bulb->getId()), 'method' => 'stop_cf', 'params' => []])."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->stopCf();
    }

    public function test_setScene()
    {
        $params = ['color', 65280, 70];
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'set_scene', 'params' => $params]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->setScene($params);
    }

    public function test_cronAdd()
    {
        $type = 0;
        $value = 15;
        $buffer = json_encode(['id' => hexdec($this->bulb->getId()), 'method' => 'cron_add', 'params' => [
            $type, $value
            ]])."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->cronAdd($type, $value);
    }

    public function test_cronGet()
    {
        $type = 0;
        $response = ['id' => 1, 'result' => ['type' => 0, 'delay' => 15, 'mix' => 0]];
        $buffer = json_encode(['id' => hexdec($this->bulb->getId()), 'method' => 'cron_get', 'params' => [
                $type
            ]])."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(json_encode($response));

        $result = $this->bulb->cronGet($type);
        $result->done(function (Response $result) use ($response) {
            $this->assertEquals(new Response($response), $result);
        });
    }

    public function test_cronDel()
    {
        $type = 0;
        $buffer = json_encode(['id' => hexdec($this->bulb->getId()), 'method' => 'cron_del', 'params' => [
                $type
            ]])."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->cronDel($type);
    }

    public function test_setAdjust()
    {
        $action = Bulb::ADJUST_ACTION_INCREASE;
        $prop = Bulb::ADJUST_ACTION_CIRCLE;
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'set_adjust', 'params' => [
                    $action, $prop
                ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->setAdjust($action, $prop);
    }

    public function test_setMusic()
    {
        $action = 0;
        $host = '192.168.0.2';
        $port = 54321;
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'set_music', 'params' => [
                    $action, $host, $port
                ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->setMusic($action, $host, $port);
    }

    public function test_setName()
    {
        $name = 'foo';
        $buffer = json_encode(
                ['id' => hexdec($this->bulb->getId()), 'method' => 'set_name', 'params' => [
                    $name
                ]]
            )."\r\n";

        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($buffer, Bulb::NO_FLAG);

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->with(Bulb::PACKET_LENGTH)
            ->willReturn(self::SUCCESS_RESPONSE);

        $this->bulb->setName($name);
    }
}
