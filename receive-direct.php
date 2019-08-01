<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Message\AMQPMessage;

class WorkerReceiver
{
  var $channel = null;

  public function listen() {
    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $this->$channel = $connection->channel();
    $queue_name = 'task_queue';
    $this->$channel->queue_declare($queue_name, false, true, false, false);
    $this->$channel->exchange_declare('logs', 'direct', false, false, false);
    //list($queue_name, ,) = $channel->queue_declare("", false, true, false, false);

    echo " [*] Waiting for messages. To exit press CTRL+C\n";
    $this->$channel->queue_bind($queue_name, 'logs');

    //This tells RabbitMQ not to give more than one message to a worker at a time
    //In order to defeat that we can use the basic_qos method with the prefetch_count = 1
    $this->$channel->basic_qos(null, 1, null);
    // Message acknowledgments are turned off by default. It's time to turn them on by
    //setting the fourth parameter to basic_consume to false (true means no ack)
    //and send a proper acknowledgment from the worker, once we're done with a task.
    //$channel->basic_consume('hello', '', false, true, false, false, $callback);
    $this->$channel->basic_consume($queue_name, '', false, false, false, false, array($this, 'process_message'));

    while ($this->$channel->is_consuming()) {
        $this->$channel->wait();
    }

    $this->$channel->close();
    $connection->close();
  }

  public function process_message(AMQPMessage $msg) {
    echo ' [x] Received ', $msg->body, "\n";
    sleep(substr_count($msg->body, '.'));
    $props = $msg->get_properties();
    echo 'retry count : ', $props['application_headers']->getNativeData()['retry'];
    if (strpos($msg->body, 'discard') != false || $props['application_headers']->getNativeData()['retry'] == 3) {
      echo ' [x] Message rejected and discarded';
      $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
    }
    elseif (strpos($msg->body, 'requeue') != false) {


      echo ' [x] Message rejected and requeued\n';
      $props['application_headers']->getNativeData()['retry'] = 4;
      //$headers = new AMQPTable(array('retry'=>$props['application_headers']->getNativeData()['retry']+1));
      //$msg->set('application_headers', $headers);
      // set second parameter to true if we want nack all messages that have not been ack-ed
      // set third paramater to true, if we wan to requeue the message
      //$msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
      $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
      sleep(substr_count($msg->body, '.'));
      $headers = new AMQPTable(array('retry'=>$props['application_headers']->getNativeData()['retry']+1));
      $msg = new AMQPMessage(
          $msg->body,
          array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json',
                'timestamp' => time(),
                'application_headers' => $headers)
      );
      $this->$channel->basic_publish($msg, 'logs');
      echo 'republished message with increament counter :', $props['application_headers']->getNativeData()['retry']+1, '\n';
    } else {
      $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }
    echo ' [x] Done with delivery_tag ', $msg->delivery_info['delivery_tag'], "\n";
  }
}

$is_keep_listen = true;
$worker = new WorkerReceiver();
while ($is_keep_listen) {
    try{
      $worker->listen();
    }
    catch(Exception $e){
        echo 'error happen, retry connect in 5 secs..\n';
        sleep(5);
    }
}


?>
