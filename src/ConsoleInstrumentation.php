<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Lumen;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use function OpenTelemetry\Instrumentation\hook;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

class ConsoleInstrumentation
{
    const RABBITMQ_COMMAND = 'rabbitmq:consume';

    public static function register(CachedInstrumentation $instrumentation): void
    {
        hook(
            Kernel::class,
            'handle',
            pre: static function (Kernel $kernel, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
                /** @psalm-suppress ArgumentTypeCoercion */
                $commandName = $params[1];

                if ($commandName === self::RABBITMQ_COMMAND) {
                    $exchangeName = $params[2];
                    $routingKey = explode('--queue=', $params[3])[1] ?? '';

                    $builder = $instrumentation->tracer()
                        ->spanBuilder($commandName . ' ' . $exchangeName . ' ' . $routingKey)
                        ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                        ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                        ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                        ->setAttribute(TraceAttributes::CODE_LINENO, $lineno)
                        ->setAttribute(TraceAttributes::MESSAGING_SYSTEM, 'amqp')
                        ->setAttribute(TraceAttributes::MESSAGING_OPERATION, 'publish')
                        ->setAttribute(TraceAttributes::MESSAGING_RABBITMQ_DESTINATION_ROUTING_KEY, $routingKey)
                        ->setAttribute(TraceAttributes::NET_PROTOCOL_NAME, 'amqp')
                        ->setAttribute(TraceAttributes::NETWORK_PROTOCOL_NAME, 'amqp')
                        ->setAttribute(TraceAttributes::NET_TRANSPORT, 'tcp')
                        ->setAttribute(TraceAttributes::NETWORK_TRANSPORT, 'tcp')
                    ;
                } else {
                    $builder = $instrumentation->tracer()
                        ->spanBuilder('Console command')
                        ->setSpanKind(SpanKind::KIND_PRODUCER)
                        ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                        ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                        ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                        ->setAttribute(TraceAttributes::CODE_LINENO, $lineno);
                }

                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));

                return $params;
            },
            post: static function (Kernel $kernel, array $params, ?int $exitCode, ?Throwable $exception) {
                $scope = Context::storage()->scope();
                if (!$scope) {
                    return;
                }

                $scope->detach();
                $span = Span::fromContext($scope->context());
                if ($exception) {
                    $span->recordException($exception, [TraceAttributes::EXCEPTION_ESCAPED => true]);
                    $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
                } elseif ($exitCode !== Command::SUCCESS) {
                    $span->setStatus(StatusCode::STATUS_ERROR);
                } else {
                    $span->setStatus(StatusCode::STATUS_OK);
                }

                $span->end();
            }
        );

        hook(
            Command::class,
            'execute',
            pre: static function (Command $command, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
                /** @psalm-suppress ArgumentTypeCoercion */
                $commandName = $params[1];

                if ($commandName === self::RABBITMQ_COMMAND) {
                    $exchangeName = $params[2];
                    $routingKey = explode('--queue=', $params[3])[1] ?? '';

                    $builder = $instrumentation->tracer()
                        ->spanBuilder($command->getName() . ' ' . $exchangeName . ' ' . $routingKey)
                        ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                        ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                        ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                        ->setAttribute(TraceAttributes::CODE_LINENO, $lineno)
                        ->setAttribute(TraceAttributes::MESSAGING_SYSTEM, 'amqp')
                        ->setAttribute(TraceAttributes::MESSAGING_OPERATION, 'publish')
                        ->setAttribute(TraceAttributes::MESSAGING_RABBITMQ_DESTINATION_ROUTING_KEY, $routingKey)
                        ->setAttribute(TraceAttributes::NET_PROTOCOL_NAME, 'amqp')
                        ->setAttribute(TraceAttributes::NETWORK_PROTOCOL_NAME, 'amqp')
                        ->setAttribute(TraceAttributes::NET_TRANSPORT, 'tcp')
                        ->setAttribute(TraceAttributes::NETWORK_TRANSPORT, 'tcp')
                    ;
                } else {
                    $builder = $instrumentation->tracer()
                        ->spanBuilder(sprintf('Command %s', $command->getName() ?: 'unknown'))
                        ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                        ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                        ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                        ->setAttribute(TraceAttributes::CODE_LINENO, $lineno);
                }


                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));

                return $params;
            },
            post: static function (Command $command, array $params, ?int $exitCode, ?Throwable $exception) {
                $scope = Context::storage()->scope();
                if (!$scope) {
                    return;
                }

                $scope->detach();
                $span = Span::fromContext($scope->context());
                $span->addEvent('command finished', [
                    'exit-code' => $exitCode,
                ]);

                if ($exception) {
                    $span->recordException($exception, [TraceAttributes::EXCEPTION_ESCAPED => true]);
                    $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
                }

                $span->end();
            }
        );
    }
}
