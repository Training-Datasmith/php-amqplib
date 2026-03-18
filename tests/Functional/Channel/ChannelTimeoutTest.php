<?php

declare(strict_types=1);

namespace PhpAmqpLib\Tests\Functional\Channel;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Helper\MiscHelper;
use PhpAmqpLib\Tests\TestCaseCompat;
use PhpAmqpLib\Wire\IO\AbstractIO;
use PhpAmqpLib\Wire\IO\StreamIO;

/**
 * @group connection
 */
class ChannelTimeoutTest extends TestCaseCompat
{
    /** @var int $channel_rpc_timeout */
    private $channel_rpc_timeout_seconds;

    /** @var int $channel_rpc_timeout */
    private $channel_rpc_timeout_microseconds;

    /** @var AbstractIO|\PHPUnit_Framework_MockObject_MockObject $io */
    private $io;

    /** @var AbstractConnection|\PHPUnit_Framework_MockObject_MockObject $connection */
    private $connection;

    /** @var AMQPChannel $channel */
    private $channel;

    private $selectResult = 1;

    protected function setUpCompat()
    {
        $channel_rpc_timeout = 3.5;

        list($this->channel_rpc_timeout_seconds, $this->channel_rpc_timeout_microseconds) =
            MiscHelper::splitSecondsMicroseconds($channel_rpc_timeout);

        $this->io = $this->getMockBuilder(StreamIO::class)
            ->setConstructorArgs([HOST, PORT, 3, 3, null, false, 0])
            ->setMethods(['select'])
            ->getMock();
        $this->io
            ->expects(self::atLeastOnce())
            ->method('select')
            ->willReturnCallback(function () {
                return $this->selectResult;
            });
        $this->connection = $this->getMockBuilder(AbstractConnection::class)
            ->setConstructorArgs([USER, PASS, '/', false, 'AMQPLAIN', null, 'en_US', $this->io, 0, 0, $channel_rpc_timeout])
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->channel = $this->connection->channel();
    }

    /**
     * @test
     *
     * @dataProvider provide_operations
     * @param string $operation
     * @param mixed[] $args
     *
     * @covers \PhpAmqpLib\Channel\AMQPChannel::exchange_declare
     * @covers \PhpAmqpLib\Channel\AMQPChannel::queue_declare
     * @covers \PhpAmqpLib\Channel\AMQPChannel::confirm_select
     */
    public function should_throw_exception_for_basic_operations_when_timeout_exceeded(string $operation, array $args)
    {
        $this->expectException(AMQPTimeoutException::class);
        $this->expectExceptionMessage('The connection timed out after 3.5 sec while awaiting incoming data');

        // simulate blocking on the I/O level
        $this->selectResult = 0;
        call_user_func_array([$this->channel, $operation], $args);
    }

    public function provide_operations()
    {
        return [
            ['exchange_declare', ['test_ex', 'fanout']],
            ['queue_declare', ['test_queue']],
            ['confirm_select', []],
        ];
    }

    protected function tearDownCompat()
    {
        $this->channel = null;
        $this->connection = null;
    }
}
