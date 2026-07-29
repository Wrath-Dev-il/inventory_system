<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = App\Models\Product::withExists('image')->find(11);
echo 'Product 11:' . PHP_EOL;
echo '  image_exists attribute: ' . json_encode($p->image_exists) . PHP_EOL;
echo '  has_image attribute: ' . json_encode($p->has_image ?? 'not set') . PHP_EOL;
echo '  toArray: ' . json_encode($p->toArray()) . PHP_EOL;

echo PHP_EOL . 'Checking normalizeProduct for product 11:' . PHP_EOL;
$ctrl = app(App\Http\Controllers\Admin\MasterListController::class);
$ref = new ReflectionMethod($ctrl, 'normalizeProduct');
$ref->setAccessible(true);
$result = $ref->invoke($ctrl, $p);
echo '  has_image: ' . json_encode($result['has_image']) . PHP_EOL;
echo '  image_version: ' . json_encode($result['image_version']) . PHP_EOL;