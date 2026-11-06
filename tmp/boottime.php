<?php
$t = microtime(true);
require "vendor/autoload.php";
printf("autoload %.2f\n", microtime(true)-$t);
$app = require "bootstrap/app.php";
printf("bootstrap/app %.2f\n", microtime(true)-$t);
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
printf("kernel bootstrap %.2f\n", microtime(true)-$t);
