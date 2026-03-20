# Architecture: php-amqplib

## Purpose

A pure PHP AMQP 0-9-1 client library for RabbitMQ and compatible brokers. Handles connection management, channel multiplexing, message publishing, consuming, and heartbeating.

## Directory Structure

```
PhpAmqpLib/
  Connection/
    AbstractConnection.php          — Core connection: frame I/O, channel dispatch, heartbeat
    AMQPStreamConnection.php        — TCP stream connection
    AMQPSocketConnection.php        — Low-level PHP socket connection
    AMQPSSLConnection.php           — TLS/SSL connection
    AMQPLazyConnection.php          — Defers connect until first channel open
    AMQPConnectionConfig.php        — Configuration value object (host, port, credentials, TLS, timeouts)
    AMQPConnectionFactory.php       — Factory that builds connections from AMQPConnectionConfig
    Heartbeat/
      PCNTLHeartbeatSender.php      — Sends heartbeats via PCNTL alarm signal
      SIGHeartbeatSender.php        — Sends heartbeats via signal handler
  Channel/
    AbstractChannel.php             — Frame dispatch, method table
    AMQPChannel.php                 — Full AMQP channel: exchange, queue, basic publish/consume
  Message/
    AMQPMessage.php                 — Represents a published or delivered message with properties
  Wire/
    AMQPReader.php                  — Reads typed values (octets, longs, strings, tables) from a buffer
    AMQPWriter.php                  — Writes typed values into a buffer
    AMQPTable.php / AMQPArray.php   — AMQP table/array types
    IO/
      StreamIO.php                  — fsockopen/fread/fwrite I/O layer
      SocketIO.php                  — socket_create/socket_recv/socket_send I/O layer
  Exchange/
    AMQPExchangeType.php            — Enum-like constants for exchange types (direct, fanout, topic, headers)
  Helper/
    MiscHelper.php                  — Utility: amqp_table_to_array, etc.
    Protocol/
      Protocol080.php / Protocol091.php — AMQP method constants and frame types
  Exception/                        — Typed exceptions for connection, channel, and I/O errors
```

## Key Design Decisions

- **Channel multiplexing**: Multiple `AMQPChannel` objects share a single TCP connection; frames are demultiplexed by channel ID in `AbstractConnection::wait_frame()`
- **Frame-based protocol**: The AMQP wire protocol is implemented by `AMQPReader`/`AMQPWriter` operating on binary buffers, with method dispatch via `AbstractChannel::$dispatch_map`
- **Blocking I/O**: The default implementation uses blocking PHP streams; non-blocking or event-loop integration requires the `AMQPSocketConnection` or a custom IO layer
- **Heartbeating**: Heartbeat sending is handled by signal-based senders (PCNTL) that fire at half the negotiated interval to keep the connection alive during long-running consumers

## Extension Points

- Implement `AbstractIO` to add a custom transport (e.g., ReactPHP streams)
- Use `AMQPConnectionFactory` with a custom `AMQPConnectionConfig` for fine-grained TLS and timeout control

## Dependency Flow

```
AMQPStreamConnection
  → AbstractConnection (frame reader/writer loop)
  → AMQPChannel (per-channel method dispatch)
      → AMQPMessage (delivered messages)
  → StreamIO / SocketIO (transport)
```
