<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Lumen\Watchers;
echo "hello watcher";
use Laravel\Lumen\Application;

abstract class Watcher
{
    /**
     * Register the watcher.
     */
    abstract public function register(Application $app): void;
}
