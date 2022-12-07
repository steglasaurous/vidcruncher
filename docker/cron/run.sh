#!/bin/bash
cd /var/www/html
php bin/console doc:mig:mig -n

while true; do
	php bin/console app:cron
	sleep 60
done
