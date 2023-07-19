<?php

declare(strict_types=1);

namespace OpenTelemetry\Tests\Unit\API\Instrumentation;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\API\Logs\LoggerInterface;
use OpenTelemetry\API\Logs\LoggerProviderInterface;
use OpenTelemetry\API\Logs\NoopLoggerProvider;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Metrics\Noop\NoopMeter;
use OpenTelemetry\API\Metrics\Noop\NoopMeterProvider;
use OpenTelemetry\API\Trace\NoopTracer;
use OpenTelemetry\API\Trace\NoopTracerProvider;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Context\Propagation\NoopTextMapPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OpenTelemetry\API\Globals
 * @covers \OpenTelemetry\API\Instrumentation\CachedInstrumentation
 * @covers \OpenTelemetry\API\Instrumentation\Configurator
 * @covers \OpenTelemetry\API\Instrumentation\ContextKeys
 */
final class InstrumentationTest extends TestCase
{
    public function test_globals_not_configured_returns_noop_instances(): void
    {
        $this->assertInstanceOf(NoopTracerProvider::class, Globals::tracerProvider());
        $this->assertInstanceOf(NoopMeterProvider::class, Globals::meterProvider());
        $this->assertInstanceOf(NoopTextMapPropagator::class, Globals::propagator());
        $this->assertInstanceOf(NoopLoggerProvider::class, Globals::loggerProvider());
    }

    public function test_globals_returns_configured_instances(): void
    {
        $tracerProvider = $this->createMock(TracerProviderInterface::class);
        $meterProvider = $this->createMock(MeterProviderInterface::class);
        $propagator = $this->createMock(TextMapPropagatorInterface::class);
        $loggerProvider = $this->createMock(LoggerProviderInterface::class);

        $scope = Configurator::create()
            ->withTracerProvider($tracerProvider)
            ->withMeterProvider($meterProvider)
            ->withPropagator($propagator)
            ->withLoggerProvider($loggerProvider)
            ->activate();

        try {
            $this->assertSame($tracerProvider, Globals::tracerProvider());
            $this->assertSame($meterProvider, Globals::meterProvider());
            $this->assertSame($propagator, Globals::propagator());
            $this->assertSame($loggerProvider, Globals::loggerProvider());
        } finally {
            $scope->detach();
        }
    }

    public function test_instrumentation_not_configured_returns_noop_instances(): void
    {
        $instrumentation = new CachedInstrumentation('', null, null, []);

        $this->assertInstanceOf(NoopTracer::class, $instrumentation->tracer());
        $this->assertInstanceOf(NoopMeter::class, $instrumentation->meter());
    }

    public function test_instrumentation_returns_configured_instances(): void
    {
        $instrumentation = new CachedInstrumentation('', null, null, []);

        $tracer = $this->createMock(TracerInterface::class);
        $tracerProvider = $this->createMock(TracerProviderInterface::class);
        $tracerProvider->method('getTracer')->willReturn($tracer);
        $meter = $this->createMock(MeterInterface::class);
        $meterProvider = $this->createMock(MeterProviderInterface::class);
        $meterProvider->method('getMeter')->willReturn($meter);
        $logger = $this->createMock(LoggerInterface::class);
        $loggerProvider = $this->createMock(LoggerProviderInterface::class);
        $loggerProvider->method('getLogger')->willReturn($logger);
        $propagator = $this->createMock(TextMapPropagatorInterface::class);

        $scope = Configurator::create()
            ->withTracerProvider($tracerProvider)
            ->withMeterProvider($meterProvider)
            ->withLoggerProvider($loggerProvider)
            ->withPropagator($propagator)
            ->activate();

        try {
            $this->assertSame($tracer, $instrumentation->tracer());
            $this->assertSame($meter, $instrumentation->meter());
            $this->assertSame($logger, $instrumentation->logger());
        } finally {
            $scope->detach();
        }
    }
}
