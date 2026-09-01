<?php

declare(strict_types=1);

namespace PhpAmqpLib\Tests\Functional;

use Httpful\Request;

class ToxiProxy
{
    /** @var string */
    private $name;

    /** @var string */
    private $api;

    /** @var string */
    private $host;

    /** @var int */
    private $listen;

    /** @var bool */
    private $isOpen = false;

    /**
     * @param string $name
     * @param string $host
     * @param int $port
     */
    public function __construct($name, $host, $port = 8474)
    {
        $this->name = $name;
        $this->host = $host;
        $this->api = 'http://' . $host . ':' . $port;
    }

    public function __destruct()
    {
        if ($this->isOpen) {
            $this->close();
        }
    }

    /**
     * Open new proxy connection to $upstream and listen on port $port.
     * @param string $upstream
     * @param int $listen
     */
    public function open($host, $port, $listen)
    {
        $this->closeIfExists();

        $payload = [
            'name' => $this->name,
            'upstream' => $host . ':' . $port,
            'listen' => ':' . $listen,
        ];
        $url = $this->api . '/proxies';
        $request = Request::post($url, json_encode($payload), 'json');
        $request->timeout(5);
        $request->expectsJson();
        $response = $request->send();
        if ($response->code !== 201) {
            throw new \RuntimeException('Cannot create Toxiproxy connection');
        }
        $this->listen = (int) $listen;
        $this->isOpen = true;
    }

    /**
     * Enable proxy $type manipulation.
     * @param string $type One of latency, bandwidth, slow_close, timeout, slicer, limit_data
     * @param array $attributes
     * @param string $direction Either upstream or downstream.
     * @param float $toxicity
     * @see https://github.com/Shopify/toxiproxy#toxics
     */
    public function mode($type, $attributes = [], $direction = 'upstream', $toxicity = 1.0)
    {
        $payload = [
            'name' => null,
            'stream' => $direction,
            'type' => $type,
            'toxicity' => $toxicity,
            'attributes' => !empty($attributes) ? $attributes : null,
        ];
        $url = sprintf('%s/proxies/%s/toxics', $this->api, $this->name);
        $request = Request::post($url, json_encode($payload), 'json');
        $request->timeout(5);
        $request->expectsJson();
        $response = $request->send();

        if ($response->code !== 200) {
            throw new \RuntimeException('Cannot set Toxiproxy connection mode');
        }
    }

    private function closeIfExists()
    {
        $url = sprintf('%s/proxies/%s', $this->api, $this->name);
        $request = Request::delete($url);
        $request->timeout(5);
        try {
            $request->send();
        } catch (\Exception $exception) {
        }
    }

    /**
     * Disable(block) proxy connection so no data can be transferred.
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function disable()
    {
        $url = sprintf('%s/proxies/%s', $this->api, $this->name);
        $response = Request::post($url, json_encode(['enabled' => false]), 'json')->send();
        if ($response->code !== 200) {
            throw new \RuntimeException('Cannot disable Toxiproxy connection');
        }
    }

    /**
     * Completely close connection to upstream.
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function close()
    {
        if (!$this->isOpen) {
            return;
        }

        $url = sprintf('%s/proxies/%s', $this->api, $this->name);
        $request = Request::delete($url);
        $request->timeout(5);
        try {
            $response = $request->send();
            if ($response->code !== 204 && $response->code !== 404) {
                throw new \RuntimeException('Cannot close Toxiproxy connection');
            }
        } catch (\Exception $exception) {
            // Best-effort cleanup; a stale proxy is removed before the next open().
        } finally {
            $this->isOpen = false;
        }
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int|null
     */
    public function getPort()
    {
        return $this->listen;
    }
}
