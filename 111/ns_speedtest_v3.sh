#!/bin/sh

#remember you're working in SHELL SCRIPTING HERE, not BASH ++_+_+_+__+_+_+++_+__+

if [ -z "$1" ] #parameter not passed after interface
then
        echo
        echo
        echo "Please pass the interface, i.e. ./ns_speedtest_v3.sh eth1"
        echo "script exiting..."
        echo
        exit 1
fi

touch speedtest.log
tail speedtest.log -f -n0 &

i="1"
sleepseconds="2"
if [ -z "$2" ] #parameter not passed after interface
then
	echo "+++++++++++++++++   To run every hour, for 24 hours, pass 'cron' after interface. i.e. ./ns_speedtest_v3.sh eth1 cron"
else
	i="24"
	sleepseconds="3600"
fi
while [ $i -gt 0 ]
do

	touch /tmp/111.tmp
	temp_file=/tmp/111.tmp

	#CTRL-C protection, still make sure background task killed
	trap trapint 2
	trapint() {
	    killall -q -s 9 wget
	    killall -q -s 9 curl
	    killall -q -s 9 tail
	    rm ${temp_file}
	    rm ns_speedtest_v3.s*
	    rm iPad_Pro_HFR* > /dev/null 2>&1
	    rm ns_1GB.zip* > /dev/null 2>&1
	    rm 1GB.zip* > /dev/null 2>&1
	    rm VTL-ST_1GB.zip > /dev/null 2>&1
	    killall -q -s 9 ns_speedtest_v3.sh

	}



	#when running on XG, it was getting bytes correct. but then when running on debian, the return from ifconfig was different, and so
	#the byytes field was in a different place, hence the if check below
	get_ispeed() {
	    if [ "$1" = "upload" ]
	    then
		field=f3
		direction=TX
	    else
		field=f2
		direction=RX
	    fi

	    bytes=$(/sbin/ifconfig $external_interface | grep bytes | grep $direction | cut -d ':' -$field | cut -d ' ' -f1);
	    if [ -z "$bytes" ]
	    then
		bytes=$(/sbin/ifconfig $external_interface | grep bytes | grep $direction | cut -d ':' -$field | cut -d ' ' -f14);
	    fi
	    echo $bytes;
	}

	write_output() {
		#don't lower this below 16, as it takes 15 seconds for curl to fail on a upload test and output error message
		secs=16
		endTime=$(( $(date +%s) + secs )) # Calculate end time.

		if [ "$1" = "upload" ]
		then
			interface="get_ispeed upload"
			killprocess=curl
			wgetrunning="yes"
		else
			interface=get_ispeed
			killprocess=curl
			wgetrunning="yes"
		fi
			sleep 1

			while [ $(date +%s) -lt $endTime ] && [ -f ${temp_file} ] && [ ! -z "$wgetrunning" ]
			do :
			    s1=`$interface`;
			    sleep 1s;
			    s2=`$interface`;
			    d=$(($s2-$s1));
			    d2=$(($d*8));
			    echo "        " $1 $(($d / 1048576))" MB/s ("$(($d2 / 1048576))"Mb/s)   | " $(date);
			    wgetrunning="$(pgrep $killprocess)"
			#echo "${wgetrunning}"
			done
			sleep 1
			killall -q -s 9 $killprocess
			sleep 1
	}

	testserver=$2
	external_interface=$1
	###########################################################################
	echo
	echo
	echo "Checking download URLs validity..."
	echo "================================="
	
	# Define URLs
	url1="http://updates-http.cdn-apple.com/2018FallFCS/fullrestores/091-62921/11701D1E-AC8E-11E8-A4EB-C9640A3A3D87/iPad_Pro_HFR_12.0_16A366_Restore.ipsw"
	url2="https://pinescore.com/111/ns_1GB.zip"
	url3="https://virtualmin-london.s3.eu-west-2.amazonaws.com/ns_1GB.zipAWS"
	url4="http://ipv4.download.thinkbroadband.com/1GB.zip"
	url5="http://84.21.152.158/ns_1GB.zipCloudLinux"
	url6="http://virtueazure.pinescore.com/VTL-ST_1GB.zip"
	
	# Check each URL
	echo -n "Apple CDN: "
	if curl -I -s --connect-timeout 3 "$url1" | grep -q "200\|302"; then echo "VALID"; else echo "INVALID/UNREACHABLE"; fi
	
	echo -n "Pinescore: "
	if curl -I -s --connect-timeout 3 "$url2" | grep -q "200\|302"; then echo "VALID"; else echo "INVALID/UNREACHABLE"; fi
	
	echo -n "AWS S3: "
	if curl -I -s --connect-timeout 3 "$url3" | grep -q "200\|302"; then echo "VALID"; else echo "INVALID/UNREACHABLE"; fi
	
	echo -n "ThinkBroadband: "
	if curl -I -s --connect-timeout 3 "$url4" | grep -q "200\|302"; then echo "VALID"; else echo "INVALID/UNREACHABLE"; fi
	
	echo -n "CloudLinux: "
	if curl -I -s --connect-timeout 3 "$url5" | grep -q "200\|302"; then echo "VALID"; else echo "INVALID/UNREACHABLE"; fi
	
	echo -n "VirtueAzure: "
	if curl -I -s --connect-timeout 3 "$url6" | grep -q "200\|302"; then echo "VALID"; else echo "INVALID/UNREACHABLE"; fi
	
	echo "================================="
	echo
	echo 4x simultaneous downloads from thinkbroadband, pinescore, AWS and apple cdn, measuring the total aggregated throuput of the external interface
        write_output download >> speedtest.log &
	curl -s -o ns_1GB.zip -O "$url1" \
	-O "$url2" \
	-O "$url3" \
	-O "$url4" \
	-O "$url5" \
	-O "$url6"

	sleep 2
	echo
	echo

	echo
	echo "Checking upload destinations validity..."
	echo "======================================="
	
	# Check FTP servers
	echo -n "VirtueAzure FTP: "
	if curl --connect-timeout 3 -s ftp://virtueazure.pinescore.com --user ftp_speedtest:ftp_speedtest 2>&1 | grep -q "530\|Permission denied" || [ $? -eq 0 ]; then
		echo "REACHABLE (auth may fail)"
	else
		echo "UNREACHABLE"
	fi
	
	echo -n "Pinescore FTP: "
	if curl --connect-timeout 3 -s ftp://pinescore.com --user ftp_speedtest.pinescore:ftp_speedtest.pinescore 2>&1 | grep -q "530\|Permission denied" || [ $? -eq 0 ]; then
		echo "REACHABLE (auth may fail)"
	else
		echo "UNREACHABLE"
	fi
	
	echo "======================================="
	echo
	echo Aggregated upload test to pinescore.com
	write_output upload >> speedtest.log &
	sleep 1
	#curl -T ns_1GB.zip -k sftp://pinescore.com:11/home/pinescore/public_html/111/ftp_speedtest/ns_sftp_1GB.zip --user ftp_speedtest.pinescore:ftp_speedtest.pinescore 2>/dev/null &
	curl -T ns_1GB.zip ftp://virtueazure.pinescore.com --user ftp_speedtest:ftp_speedtest 2>/dev/null &
	curl -T ns_1GB.zip ftp://pinescore.com --user ftp_speedtest.pinescore:ftp_speedtest.pinescore 2>/dev/null

	rm iPad_Pro_HFR* > /dev/null 2>&1
	rm ns_1GB.zip* > /dev/null 2>&1
	rm 1GB.zip* > /dev/null 2>&1
	rm VTL-ST_1GB.zip > /dev/null 2>&1

	
	#dev/null output because if we CTL-C, it removes the file once as part of the trap, then tries again here and throws error
	rm ${temp_file} 2> /dev/null

	#sleep solves a race condition on debian causing scipt to hang a third of the time otherwise. think it's because
	#the commands finish the background tasks have yet not complete
	#echo "sleeping for $sleepseconds second(s), $((i-1)) iterations left"
	sleep $sleepseconds
	i=$((i-1))
done
killall -q -s 9 tail
echo
echo " +++++++++++++++++   deleting script, if you'd like to run again, please pull the latest version from https://pinescore.com/111/ns_speedtest_v3.sh"
echo " +++++++++++++++++   example one liner: wget https://pinescore.com/111/ns_speedtest_v3.sh --no-check-certificate && chmod +x ns_speedtest_v3.sh && ./ns_speedtest_v3.sh eth1"
rm ns_speedtest_v3.s*
rm iPad_Pro_HFR* > /dev/null 2>&1
rm ns_1GB.zip* > /dev/null 2>&1
rm 1GB.zip* > /dev/null 2>&1
rm VTL-ST_1GB.zip > /dev/null 2>&1
