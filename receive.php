<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$queue_name = 'task_queue';
$channel->queue_declare($queue_name, false, true, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
  echo ' [x] Received ', $msg->body, "\n";
  sleep(substr_count($msg->body, '.'));
  echo ' [x] Done with delivery_tag ', $msg->delivery_info['delivery_tag'], "\n";
  $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};


//This tells RabbitMQ not to give more than one message to a worker at a time
//In order to defeat that we can use the basic_qos method with the prefetch_count = 1
$channel->basic_qos(null, 1, null);
// Message acknowledgments are turned off by default. It's time to turn them on by
//setting the fourth parameter to basic_consume to false (true means no ack)
//and send a proper acknowledgment from the worker, once we're done with a task.
//$channel->basic_consume('hello', '', false, true, false, false, $callback);
$channel->basic_consume($queue_name, '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();

?>
