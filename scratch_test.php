<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$f = Faker\Factory::create();
echo "Title 1: " . rtrim($f->realText(30), ' .') . "\n";
echo "Title 2: " . rtrim($f->realText(40), ' .') . "\n";
echo "Title 3: " . rtrim($f->realText(50), ' .') . "\n";
echo "Body: \n" . $f->realText(400) . "\n";
