<?php

declare(strict_types=1);

namespace PhpAmqpLib\Connection;

/**
 * @deprecated Use AMQPConnectionFactory with AMQPConnectionConfig::setIsSecure(true) and AMQPConnectionConfig::setSsl* methods.
 */
class AMQPSSLConnection extends AMQPStreamConnection
{
    /**
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $password
     * @param string $vhost
     * @param array $ssl_options
     * @param array $options
     * @throws \Exception
     */
    public function __construct(
        $host,
        $port,
        $user,
        $password,
        $vhost = '/',
        $ssl_options = [],
        $options = [],
        ?AMQPConnectionConfig $config = null
    ) {
        trigger_error('AMQPSSLConnection is deprecated and will be removed in version 4 of php-amqplib', E_USER_DEPRECATED);
        $ssl_context = null;
        if (!empty($ssl_options)) {
            $ssl_context = $this->createSslContext($ssl_options);
        }

        parent::__construct(
            $host,
            $port,
            $user,
            $password,
            $vhost,
            $options['insist'] ?? false,
            $options['login_method'] ?? 'AMQPLAIN',
            $options['login_response'] ?? null,
            $options['locale'] ?? 'en_US',
            $options['connection_timeout'] ?? 3,
            $options['read_write_timeout'] ?? 130,
            $ssl_context,
            $options['keepalive'] ?? false,
            $options['heartbeat'] ?? 0,
            $options['channel_rpc_timeout'] ?? 0.0,
            $config
        );
    }

    /**
     * @deprecated Use AmqpConnectionFactory
     * @throws \Exception
     */
    public static function try_create_connection($host, $port, $user, $password, $vhost, $options): self
    {
        $ssl_options = $options['ssl_options'] ?? [];
        return new static($host, $port, $user, $password, $vhost, $ssl_options, $options);
    }

    /**
     * @param array $options
     * @return resource
     */
    private function createSslContext($options)
    {
        $ssl_context = stream_context_create();
        foreach ($options as $k => $v) {
            // Note: 'ssl' applies to 'tls' as well
            // https://www.php.net/manual/en/context.ssl.php
            stream_context_set_option($ssl_context, 'ssl', $k, $v);
        }

        return $ssl_context;
    }
}
