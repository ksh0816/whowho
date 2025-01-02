#!/bin/bash
export LANG=ko_KR.eucKR
PS="/bin/ps"
GREP="/bin/grep"

if ! $PS ax | $GREP php | $GREP -w "whowho_send_api" > /dev/null 2>&1; then
   cd /var/www/html/whowho
   /usr/bin/php whowho_send_api.php &
fi
exit
