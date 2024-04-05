<?php

declare(strict_types=1);

use OpenTelemetry\Contrib\Instrumentation\Lumen\LumenInstrumentation;
use OpenTelemetry\SDK\Sdk;

if (class_exists(Sdk::class) && Sdk::isInstrumentationDisabled(LumenInstrumentation::NAME) === true) {
    return;
}

if (extension_loaded('opentelemetry') === false) {
    trigger_error('The opentelemetry extension must be loaded in order to autoload the OpenTelemetry Laravel auto-instrumentation', E_USER_WARNING);

    return;
}

$jaegerHost = env('JAEGER_HOST', 'http://localhost:4318');
putenv('OTEL_SDK_DISABLED=false');
putenv('OTEL_SERVICE_NAME=' . env('APP_NAME', 'jmart-lumen-core'));
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_TRACES_EXPORTER=otlp');
putenv('OTEL_METRICS_EXPORTER=none');
putenv('OTEL_LOGS_EXPORTER=none');
putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://jaeger.loc:4318' . $jaegerHost);
putenv('OTEL_EXPORTER_OTLP_TRACES_ENDPOINT=' . $jaegerHost . '/v1/traces');
putenv('OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf');
putenv('OTEL_PROPAGATORS=baggage,tracecontext');
putenv('OTEL_TRACES_SAMPLER=parentbased_always_on');
putenv('OTEL_PHP_DETECTORS=all');

LumenInstrumentation::register();
