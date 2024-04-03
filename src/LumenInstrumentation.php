<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Lumen;

use Illuminate\Contracts\Foundation\Application;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\Contrib\Instrumentation\Lumen\Watchers\CacheWatcher;
use OpenTelemetry\Contrib\Instrumentation\Lumen\Watchers\ClientRequestWatcher;
use OpenTelemetry\Contrib\Instrumentation\Lumen\Watchers\ExceptionWatcher;
use OpenTelemetry\Contrib\Instrumentation\Lumen\Watchers\LogWatcher;
use OpenTelemetry\Contrib\Instrumentation\Lumen\Watchers\QueryWatcher;
use OpenTelemetry\Contrib\Instrumentation\Lumen\Watchers\RequestWatcher;
use OpenTelemetry\Contrib\Instrumentation\Lumen\Watchers\Watcher;
use function OpenTelemetry\Instrumentation\hook;
use Throwable;

class LumenInstrumentation
{
    public const NAME = 'lumen';

    public static function registerWatchers(Application $app, Watcher $watcher)
    {
        $watcher->register($app);
    }

    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation('io.opentelemetry.contrib.php.lumen');

        hook(
            Application::class,
            '__construct',
            post: static function (Application $application, array $params, mixed $returnValue, ?Throwable $exception) use ($instrumentation) {
                self::registerWatchers($application, new CacheWatcher());
                self::registerWatchers($application, new ClientRequestWatcher($instrumentation));
                self::registerWatchers($application, new ExceptionWatcher());
                self::registerWatchers($application, new LogWatcher());
                self::registerWatchers($application, new QueryWatcher($instrumentation));
                self::registerWatchers($application, new RequestWatcher());
            },
        );

        ConsoleInstrumentation::register($instrumentation);
        HttpInstrumentation::register($instrumentation);
    }
}
