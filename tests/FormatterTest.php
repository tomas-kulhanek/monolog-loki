<?php

namespace TomasKulhanek\Tests\Monolog\Loki;

use TomasKulhanek\Monolog\Loki\LokiJsonFormatter;

class FormatterTest extends \PHPUnit\Framework\TestCase
{
    public function testJsonFormat(): void
    {
        $input = new \Monolog\LogRecord(
            datetime: new \DateTimeImmutable("2021-08-10T14:49:47.618908+00:00"),
            channel: 'name',
            level: \Monolog\Level::Debug,
            message: 'some message',
            context: [],
            extra: ['x' => 'y'],
        );

        $json = (new LokiJsonFormatter([]))->format($input);

        try {
            $jsonData = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
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
        self::assertIsArray($jsonData['streams'][0]['stream']);
        self::assertArrayHasKey('values', $jsonData['streams'][0]);
        self::assertIsArray($jsonData['streams'][0]['values']);
        self::assertCount(1, $jsonData['streams'][0]['values']);

        self::assertIsArray($jsonData['streams'][0]['values'][0]);
        self::assertIsString($jsonData['streams'][0]['values'][0][1]);

        self::assertSame('1628606987618908000', $jsonData['streams'][0]['values'][0][0]);
        self::assertSame(
            '{"message":"some message","context":{},"level":100,"level_name":"DEBUG","channel":"name","datetime":"2021-08-10T14:49:47+00:00","extra":{"x":"y"}}',
            $jsonData['streams'][0]['values'][0][1]
        );
    }

    public function testJsonBatchFormat(): void
    {
        $input = [
            new \Monolog\LogRecord(
                datetime: new \DateTimeImmutable("2021-08-10T14:49:47.618908+00:00"),
                channel: 'name',
                level: \Monolog\Level::Debug,
                message: 'some message',
                context: [],
                extra: ['x' => 'y'],
            ),
            new \Monolog\LogRecord(
                datetime: new \DateTimeImmutable("2022-08-10T14:49:47.618908+00:00"),
                channel: 'name',
                level: \Monolog\Level::Critical,
                message: 'second message',
                context: ["some context"],
                extra: ['x' => 'z'],
            ),
        ];

        $json = (new LokiJsonFormatter(['app' => 'application name']))->formatBatch($input);

        try {
            $jsonData = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
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
        self::assertIsArray($jsonData['streams'][0]['stream']);
        self::assertCount(1, $jsonData['streams'][0]['stream']);
        self::assertArrayHasKey('app', $jsonData['streams'][0]['stream']);
        self::assertSame('application name', $jsonData['streams'][0]['stream']['app']);
        self::assertArrayHasKey('values', $jsonData['streams'][0]);
        self::assertIsArray($jsonData['streams'][0]['values']);
        self::assertCount(2, $jsonData['streams'][0]['values']);

        self::assertIsArray($jsonData['streams'][0]['values'][0]);
        self::assertIsString($jsonData['streams'][0]['values'][0][1]);
        self::assertSame('1628606987618908000', $jsonData['streams'][0]['values'][0][0]);
        self::assertSame(
            '{"message":"some message","context":{},"level":100,"level_name":"DEBUG","channel":"name","datetime":"2021-08-10T14:49:47+00:00","extra":{"x":"y"}}',
            $jsonData['streams'][0]['values'][0][1]
        );
        self::assertIsArray($jsonData['streams'][0]['values'][1]);
        self::assertIsString($jsonData['streams'][0]['values'][1][1]);

        self::assertSame('1660142987618908000', $jsonData['streams'][0]['values'][1][0]);
        self::assertSame(
            '{"message":"second message","context":["some context"],"level":500,"level_name":"CRITICAL","channel":"name","datetime":"2022-08-10T14:49:47+00:00","extra":{"x":"z"}}',
            $jsonData['streams'][0]['values'][1][1]
        );
    }
}
