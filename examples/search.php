<?php

require __DIR__ . '/../vendor/autoload.php';


$store = new \Meklis\BillingNetworkHelpers\Dummy\Store();

$search = new \Meklis\BillingNetworkHelpers\SearchIP($store);

$search->setProxyConfigurationPath(__DIR__ . "/proxies.yml")->setIp($argv[1]);


print_r($search->search());