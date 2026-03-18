<?php

declare(strict_types=1);

namespace PhpAmqpLib\Exchange;

final class AMQPExchangeType
{
    public const DIRECT = 'direct';
    public const FANOUT = 'fanout';
    public const TOPIC = 'topic';
    public const HEADERS = 'headers';
}
