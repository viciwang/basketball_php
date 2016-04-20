baseurl=`ifconfig en0 | grep inet | grep -v inet6 | awk '{print $2}'`:8081
php -S $baseurl
