#!/bin/bash
cd /var/www/html

while true; do
	php bin/console app:cron
	sleep 60
done
