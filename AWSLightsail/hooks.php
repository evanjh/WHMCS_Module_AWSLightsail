<?php
require_once __DIR__ . '/func.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Aws\Lightsail\LightsailClient;

set_time_limit(0);

add_hook("DailyCronJob", 1, function ($vars) {
    if (date("d") != "01") {
        return;
    }

    $products = Capsule::table('tblproducts')->where('servertype', 'AWSLightsail')->get();
    foreach ($products as $product) {
        $hostings = Capsule::table('tblhosting')->where('packageid', $product->id)->get();
        foreach ($hostings as $hosting) {
            Capsule::table('tblhosting')->where('id', $hosting->id)->update(['bwusage' => 0]);
            if ($hosting->domainstatus == 'Suspended' && $hosting->suspendreason == "OutOfTraffic") {
                localAPI("ModuleUnsuspend", ['serviceid' => $hosting->id]);
            }
        }
    }
});

add_hook("AfterCronJob", 1, function ($vars) {
    $products = Capsule::table('tblproducts')->where('servertype', 'AWSLightsail')->get();
    foreach ($products as $product) {
        $hostings = Capsule::table('tblhosting')->where('packageid', $product->id)->get();
        foreach ($hostings as $hosting) {

            $client = new LightsailClient([
                'region' => $product->configoption3,
                'version' => '2016-11-28',
                'credentials' => [
                    'key' => $product->configoption1,
                    'secret' => $product->configoption2
                ],
            ]);

            $usage_in = $client->getInstanceMetricData([
                'endTime' => time(), // REQUIRED
                'instanceName' => $product->configoption7 . $hosting->id, // REQUIRED
                'metricName' => 'NetworkIn', // REQUIRED
                'period' => 2700000, // REQUIRED
                'startTime' => strtotime(date("Y-m")), // REQUIRED
                'statistics' => ['Sum'], // REQUIRED
                'unit' => 'Bytes', // REQUIRED
            ]);

            $usage_out = $client->getInstanceMetricData([
                'endTime' => time(), // REQUIRED
                'instanceName' => $product->configoption7 . $hosting->id, // REQUIRED
                'metricName' => 'NetworkOut', // REQUIRED
                'period' => 2700000, // REQUIRED
                'startTime' => strtotime(date("Y-m")), // REQUIRED
                'statistics' => ['Sum'], // REQUIRED
                'unit' => 'Bytes', // REQUIRED
            ]);

            $used = ceil(($usage_in['metricData'][0]['sum'] + $usage_out['metricData'][0]['sum']) / 1048576);
            Capsule::table('tblhosting')->where('id', $hosting->id)->update(['bwusage' => $used, 'lastupdate' => date('Y-m-d H:i:s')]);

            if ($used >= $hosting->bwlimit && $hosting->domainstatus == 'Active') {
                localAPI("ModuleSuspend", ['serviceid' => $hosting->id, 'suspendreason' => 'OutOfTraffic']);
                continue;
            }
        }
    }
});
