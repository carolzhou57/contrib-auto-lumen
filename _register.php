<?php

declare(strict_types=1);

use OpenTelemetry\Contrib\Instrumentation\Lumen\LumenInstrumentation;
use OpenTelemetry\SDK\Resource\Detectors\Composer;
use OpenTelemetry\SDK\Sdk;

if (class_exists(Sdk::class) && Sdk::isInstrumentationDisabled(LumenInstrumentation::NAME) === true) {
    return;
}

if (extension_loaded('opentelemetry') === false) {
    trigger_error('The opentelemetry extension must be loaded in order to autoload the OpenTelemetry Laravel auto-instrumentation', E_USER_WARNING);

    return;
}

//$composerAttributes = new Composer();
//$serviceName = $composerAttributes->getResource()->getAttributes()->get('service.name');
//putenv('OTEL_SERVICE_NAME=' . $serviceName);

LumenInstrumentation::register();
