#!/bin/bash

php /home/mike/it490/DB/consumer.php &
PID1=$!

php /home/mike/it490/rabbitmq/producer.php &
PID2=$!

php /home/mike/it490/DMZ/dmzconsumer.php &
PID3=$!

wait -n

kill $PID1 $PID2 $PID3
