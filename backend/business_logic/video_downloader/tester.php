<?php

exec('python main.py "cookie_microstats_visibility=1; login_token=4153c8ae1641f2259a4e785b5efd81d5; PHPSESSID=elb~dkim6f9srka56g9u6td9s032q6" "https://lms.code.edu.az/unit/view/id:6456"', $err, $code);

var_dump($err, $code);
