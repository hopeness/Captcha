<?php
require 'Captcha.class.php';
$verification = new Captcha(200, 100);
$captcha = 'ab12';
echo $verification->show($captcha);
