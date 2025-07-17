<?php

namespace TomasKulhanek\Tests\Monolog\Loki;

use Monolog\Formatter\LineFormatter;
use TomasKulhanek\Monolog\Loki\LokiHandler;
use TomasKulhanek\Monolog\Loki\SynchronousLokiHandler;
use TomasKulhanek\Tests\Monolog\Loki\Support\MockClient;

class HandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testHandlerWrite(): void
    {
        $mockClient = new MockClient();
        $handler = new SynchronousLokiHandler(
            'username',
            'password',
            'endpoint',
            level: \Monolog\Level::Debug,
            client: $mockClient
        );

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');

        self::assertIsString($mockClient->capturedData);
        try {
            $jsonData = json_decode($mockClient->capturedData, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            self::fail('The formatted data is not valid JSON: ' . $exception->getMessage());
        }

        self::assertEquals(0, json_last_error(), "The formatted data is not valid JSON");
        self::assertIsArray($jsonData, "Expected array of logs");
        self::assertArrayHasKey('streams', $jsonData);
        self::assertIsArray($jsonData['streams']);
        self::assertArrayHasKey(0, $jsonData['streams']);
        self::assertIsArray($jsonData['streams'][0]);
        self::assertArrayHasKey('stream', $jsonData['streams'][0]);
        self::assertArrayHasKey('values', $jsonData['streams'][0]);
        self::assertIsArray($jsonData['streams'][0]['values']);
        self::assertCount(1, $jsonData['streams'][0]['values']);

        self::assertIsArray($jsonData['streams'][0]['values'][0]);
        self::assertIsString($jsonData['streams'][0]['values'][0][1]);
        self::assertStringContainsString('test message', $jsonData['streams'][0]['values'][0][1]);
    }

    public function testHandlerWriteWithLineFormatter(): void
    {
        $mockClient = new MockClient();
        $synchronousHandler = new SynchronousLokiHandler(
            'username',
            'password',
            'endpoint',
            level: \Monolog\Level::Debug,
            bubble: false,
            client: $mockClient
        );
        $handler = new LokiHandler(
            'endpoint',
            'username',
            'password',
            level: \Monolog\Level::Debug,
            bubble: false,
            syncHandler: $synchronousHandler
        );
        $handler->setFormatter(new LineFormatter());

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');

        self::assertNull($mockClient->capturedData);
    }

    public function testHandlerWriteWithBatchWrite(): void
    {
        $mockClient = new MockClient();
        $synchronousHandler = new SynchronousLokiHandler(
            'username',
            'password',
            'endpoint',
            level: \Monolog\Level::Debug,
            bubble: false,
            client: $mockClient
        );
        $handler = new LokiHandler(
            'endpoint',
            'username',
            'password',
            level: \Monolog\Level::Debug,
            bubble: false,
            syncHandler: $synchronousHandler
        );

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');
        $logger->debug('test message2');
        $handler->flush();

        self::assertIsString($mockClient->capturedData);
        try {
            $jsonData = json_decode($mockClient->capturedData, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            self::fail('The formatted data is not valid JSON: ' . $exception->getMessage());
        }

        self::assertEquals(0, json_last_error(), "The formatted data is not valid JSON");
        self::assertIsArray($jsonData, "Expected array of logs");
        self::assertArrayHasKey('streams', $jsonData);
        self::assertIsArray($jsonData['streams']);
        self::assertArrayHasKey(0, $jsonData['streams']);
        self::assertIsArray($jsonData['streams'][0]);
        self::assertArrayHasKey('stream', $jsonData['streams'][0]);
        self::assertArrayHasKey('values', $jsonData['streams'][0]);
        self::assertIsArray($jsonData['streams'][0]['values']);
        self::assertCount(2, $jsonData['streams'][0]['values']);

        self::assertIsArray($jsonData['streams'][0]['values'][0]);
        self::assertIsString($jsonData['streams'][0]['values'][0][1]);
        self::assertStringContainsString('test message', $jsonData['streams'][0]['values'][0][1]);
        self::assertIsArray($jsonData['streams'][0]['values'][1]);
        self::assertIsString($jsonData['streams'][0]['values'][1][1]);
        self::assertStringContainsString('test message2', $jsonData['streams'][0]['values'][1][1]);
    }
}
