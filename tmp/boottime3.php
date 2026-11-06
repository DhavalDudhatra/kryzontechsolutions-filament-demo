<?php
$t = microtime(true);
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$r = new ReflectionMethod($kernel, "bootstrappers");
$r->setAccessible(true);
foreach ($r->invoke($kernel) as $b) {
    if ($b === Illuminate\Foundation\Bootstrap\BootProviders::class) continue;
    $app->make($b)->bootstrap($app);
}
printf("pre-boot %.2f\n", microtime(true)-$t);
$bp = new ReflectionMethod($app, "bootProvider");
$bp->setAccessible(true);
foreach (array_keys($app->getLoadedProviders()) as $name) {
    $p = $app->getProvider($name);
    $s = microtime(true);
    $bp->invoke($app, $p);
    $d = microtime(true)-$s;
    if ($d > 0.2) printf("%.2f %s\n", $d, $name);
}
printf("total %.2f\n", microtime(true)-$t);
