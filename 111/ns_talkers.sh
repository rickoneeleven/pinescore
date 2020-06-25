#!/bin/sh

echo Top 10 Talkers, ver 0.1
echo

#CTRL-C to stop
trap trapint 2
trapint() {
    rm talkers.log
    rm ns_talkers.sh
    killall -q -s 9 tcpdump
    killall -q -s 9 ns_talkers.sh
}

tcpdump -tnn -c 50000 -i $1  | awk -F "." '{print $1"."$2"."$3"."$4}' | sort | uniq -c | sort -nr | awk ' $1 > 100 ' > talkers.log
head talkers.log -n10
./ns_talkers.sh $1


