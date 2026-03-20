<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exchange\AMQPExchangeType;

// --- Example 1: Publish a message ---
$connection = new AMQPStreamConnection(
    host: 'localhost',
    port: 5672,
    user: 'guest',
    password: 'guest',
    vhost: '/'
);
$channel = $connection->channel();

// Declare the exchange and queue
$channel->exchange_declare('orders', AMQPExchangeType::DIRECT, false, true, false);
$channel->queue_declare('order_processing', false, true, false, false);
$channel->queue_bind('order_processing', 'orders', 'new_order');

// Publish a message
$message = new AMQPMessage(
    json_encode(['order_id' => 12345, 'customer' => 'Alice', 'total' => 99.99]),
    [
        'content_type'  => 'application/json',
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    ]
);
$channel->basic_publish($message, 'orders', 'new_order');
echo "Order message published.\n\n";

$channel->close();
$connection->close();

// --- Example 2: Consume messages (run as a separate long-running process) ---
echo "To consume messages, run a consumer process like this:\n\n";
echo <<<'CONSUMER'
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$callback = function (AMQPMessage $message): void {
    $data = json_decode($message->getBody(), true);
    echo "Processing order #{$data['order_id']} for {$data['customer']}\n";

    // Acknowledge the message so RabbitMQ removes it from the queue
    $message->ack();
};

$channel->basic_qos(null, 1, null);  // process one message at a time
$channel->basic_consume('order_processing', '', false, false, false, false, $callback);

echo "Waiting for messages. CTRL+C to exit.\n";
while ($channel->is_consuming()) {
    $channel->wait();
}
CONSUMER;
echo "\n";
