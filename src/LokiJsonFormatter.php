<?php

declare(strict_types=1);

namespace TomasKulhanek\Monolog\Loki;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;
use Monolog\Utils;

class LokiJsonFormatter extends JsonFormatter
{
    /**
     * @param array<string, string> $labels
     */
    public function __construct(private readonly array $labels = [])
    {
        parent::__construct(self::BATCH_MODE_NEWLINES, includeStacktraces: true);
        $this->setMaxNormalizeItemCount(PHP_INT_MAX);
    }

    public function format(LogRecord $record): string
    {
        return $this->formatBatch([$record]);
    }

    protected function toJson($data, bool $ignoreErrors = false): string
    {
        return Utils::jsonEncode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE, $ignoreErrors);
    }

    /**
     * @inheritDoc
     */
    public function formatBatch(array $records): string
    {
        return $this->toJson([
            'streams' => [
                [
                    'stream' => $this->labels,
                    'values' => array_map(fn(LogRecord $record) => [
                        $this->getNanosecondTimestamp($record->datetime),
                        $this->toJson($this->normalizeRecord($record))
                    ], $records)
                ]
            ]
        ], true);
    }

    private function getNanosecondTimestamp(\DateTimeInterface $dt): string
    {
        return (string) ($dt->getTimestamp() * 1_000_000_000 + ((int) $dt->format('u')) * 1_000);
    }
}
