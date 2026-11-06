<?php
$t = microtime(true);
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$r = new ReflectionMethod($kernel, "bootstrappers");
$r->setAccessible(true);
foreach ($r->invoke($kernel) as $b) {
    $s = microtime(true);
    $app->make($b)->bootstrap($app);
    printf("%s %.2f\n", $b, microtime(true)-$s);
}
printf("bootstrappers total %.2f\n", microtime(true)-$t);
$s = microtime(true);
$app->boot();
printf("app boot %.2f\n", microtime(true)-$s);
printf("total %.2f\n", microtime(true)-$t);
