<?php

include("../../../inc/includes.php");

$provider = new GlpiPlugin\Carbon\CarbonDataProviderFrance();

print_r($provider->getCarbonIntensity('FR', '', '', new DateTime()));
