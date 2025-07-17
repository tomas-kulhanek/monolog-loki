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
        self::assertSame('{"streams":[{"stream":[],"values":[["1628606987618908000","{\"message\":\"some message\",\"context\":{},\"level\":100,\"level_name\":\"DEBUG\",\"channel\":\"name\",\"datetime\":\"2021-08-10T14:49:47+00:00\",\"extra\":{\"x\":\"y\"}}"]]}]}', $json);
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

        $json = (new LokiJsonFormatter([]))->formatBatch($input);
        self::assertSame('{"streams":[{"stream":[],"values":[["1628606987618908000","{\"message\":\"some message\",\"context\":{},\"level\":100,\"level_name\":\"DEBUG\",\"channel\":\"name\",\"datetime\":\"2021-08-10T14:49:47+00:00\",\"extra\":{\"x\":\"y\"}}"],["1660142987618908000","{\"message\":\"second message\",\"context\":[\"some context\"],\"level\":500,\"level_name\":\"CRITICAL\",\"channel\":\"name\",\"datetime\":\"2022-08-10T14:49:47+00:00\",\"extra\":{\"x\":\"z\"}}"]]}]}', $json);
    }
}
