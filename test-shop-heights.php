<?php

require 'vendor/autoload.php';

use App\Services\DrawerConfiguratorService;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$service = app(DrawerConfiguratorService::class);

echo "=== Shop Values Test (12\" W × 6\" H × 19\" D) ===\n\n";

$result = $service->calculateDrawerDimensions(12, 6, 19, 0.5);

echo "DRAWER BOX DIMENSIONS:\n";
echo "  Height:\n";
echo "    Theoretical: " . $result['drawer_box']['height'] . "\" (" . DrawerConfiguratorService::toFraction($result['drawer_box']['height']) . ")\n";
echo "    Shop (↓½\"):  " . $result['drawer_box']['height_shop'] . "\" (" . DrawerConfiguratorService::toFraction($result['drawer_box']['height_shop']) . ")\n";
echo "  Depth:\n";
echo "    Theoretical: " . $result['drawer_box']['depth'] . "\" (" . DrawerConfiguratorService::toFraction($result['drawer_box']['depth']) . ")\n";
echo "    Shop (+¼\"):  " . $result['drawer_box']['depth_shop'] . "\" (" . DrawerConfiguratorService::toFraction($result['drawer_box']['depth_shop']) . ")\n";

echo "\n=== Cut List with Shop Values ===\n\n";
$cutList = $service->getCutList(12, 6, 19, 0.5);

foreach ($cutList['cut_list'] as $piece => $spec) {
    $shopHeight = $spec['width_shop'] ?? $spec['width'];
    $shopLength = $spec['length_shop'] ?? $spec['length'];
    
    echo strtoupper($piece) . " (×" . $spec['quantity'] . "):\n";
    echo "  Theoretical: " . DrawerConfiguratorService::toFraction($spec['width']) . " × " . DrawerConfiguratorService::toFraction($spec['length']) . "\n";
    echo "  Shop:        " . DrawerConfiguratorService::toFraction($shopHeight) . " × " . DrawerConfiguratorService::toFraction($shopLength) . "\n\n";
}

echo "=== Shop Values Test (20\" W × 6\" H × 13\" D) ===\n\n";

$result2 = $service->calculateDrawerDimensions(20, 6, 13, 0.5);

echo "DRAWER BOX DIMENSIONS:\n";
echo "  Height:\n";
echo "    Theoretical: " . $result2['drawer_box']['height'] . "\" (" . DrawerConfiguratorService::toFraction($result2['drawer_box']['height']) . ")\n";
echo "    Shop (↓½\"):  " . $result2['drawer_box']['height_shop'] . "\" (" . DrawerConfiguratorService::toFraction($result2['drawer_box']['height_shop']) . ")\n";
echo "  Depth:\n";
echo "    Theoretical: " . $result2['drawer_box']['depth'] . "\" (" . DrawerConfiguratorService::toFraction($result2['drawer_box']['depth']) . ")\n";
echo "    Shop (+¼\"):  " . $result2['drawer_box']['depth_shop'] . "\" (" . DrawerConfiguratorService::toFraction($result2['drawer_box']['depth_shop']) . ")\n";
