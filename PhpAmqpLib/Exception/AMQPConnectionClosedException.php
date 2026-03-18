<?php

declare(strict_types=1);

namespace PhpAmqpLib\Exception;

/**
 * When connection was closed by server, proxy or some tunnel due to timeout or network issue.
 */
class AMQPConnectionClosedException extends AMQPRuntimeException
{
}
