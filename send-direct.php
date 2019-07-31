<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "info: default data!";
}

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$queue_name = 'task_queue';
// $channel->queue_declare('hello', false, false, false, false);
//$channel->queue_declare($queue_name, false, true, false, false);
$channel->exchange_declare('logs', 'direct', false, false, false);

// $msg = new AMQPMessage($data);
$msg = new AMQPMessage(
    $data,
    array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
);
$channel->basic_publish($msg, 'logs');
//$channel->basic_publish($msg, '', $queue_name);

echo " [x] Sent 'Hello World!'\n";


$channel->close();
$connection->close();

?>
