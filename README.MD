# Simple-Rabbit
This code only for exploration purpose, need more tweak if it will be used on production level, currently covering scenario :
* Configure durable queue
* Send persistent message (producer consumer and publisher subscriber scenario)
* Consume message
* Retry 3 times scenario
* Reject after 3 time retrying
* Reconnect if error (lost connection and queue deleted)

Run receiver first so that queue created first, or the published message will be discarded since we are trying sending message through direct exchange.

## Sample command :
* Start receiver ```php receive-direct.php ```
* Send normal message ```php send-direct.php test```
* Send requeue message that will be discarded after 3 times retry ```php send-direct.php test requeue```

## Setup docker rabbitmq :
* Original image from https://hub.docker.com/_/rabbitmq
* Run a rabbitmq container with command
```docker run -d --hostname my-rabbit --name some-rabbit -p 8080:15672 -p 5672:5672 rabbitmq:3-management```. It will open port on host level 8080 for web management with default username guest and password guest, also open port 5672 for the sample app can connect

## Installation AMPQ Extentions
* [Setup composer]https://getcomposer.org/download/
* [Basic usage composer]https://getcomposer.org/doc/01-basic-usage.md
* [RabbitMQ Hello Wordl]https://www.rabbitmq.com/tutorials/tutorial-one-php.html
