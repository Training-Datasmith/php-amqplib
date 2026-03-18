<?php

declare(strict_types=1);

namespace PhpAmqpLib\Exception;

class AMQPTimeoutException extends \RuntimeException implements AMQPExceptionInterface
{
    /**
     * @var int|float|null
     */
    private $timeout;

    public function __construct($message = '', $timeout = 0, $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->timeout = $timeout;
    }

    /**
     * @param int|float|null $timeout
     * @param int $code
     */
    public static function writeTimeout($timeout, $code = 0): self
    {
        return new self('Error sending data. Connection timed out.', $timeout, $code);
    }

    /**
     * @return int|float|null
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
}
