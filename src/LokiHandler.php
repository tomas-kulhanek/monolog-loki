<?php

declare(strict_types=1);

namespace TomasKulhanek\Monolog\Loki;

use Monolog\Handler\BufferHandler;
use Monolog\Level;
use Monolog\LogRecord;

class LokiHandler extends BufferHandler
{
    public const DEFAULT_BUBBLE = true;
    public const DEFAULT_BUFFER_LIMIT = 1000;
    public const DEFAULT_FLUSH_ON_OVERFLOW = true;
    public const DEFAULT_FLUSH_INTERVAL_MILLISECONDS = 5000;
    private ?float $highResolutionTimeOfNextFlush;
    private readonly SynchronousLokiHandler $syncHandler;

    /**
     * @param array<string, string> $labels
     */
    public function __construct(
        string $endpoint,
        string $username,
        string $password,
        array $labels = [],
        int|string|Level $level = Level::Debug,
        bool $bubble = self::DEFAULT_BUBBLE,
        int $bufferLimit = self::DEFAULT_BUFFER_LIMIT,
        bool $flushOnOverflow = self::DEFAULT_FLUSH_ON_OVERFLOW,
        int $connectionTimeoutMs = LokiClient::DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS,
        int $timeoutMs = LokiClient::DEFAULT_TIMEOUT_MILLISECONDS,
        private readonly ?int $flushIntervalMs = self::DEFAULT_FLUSH_INTERVAL_MILLISECONDS,
        bool $throwExceptions = SynchronousLokiHandler::DEFAULT_THROW_EXCEPTION,
        ?SynchronousLokiHandler $syncHandler = null
    ) {
        $this->syncHandler = $syncHandler ?? new SynchronousLokiHandler(
            $username,
            $password,
            $endpoint,
            $level,
            $labels,
            $bubble,
            $connectionTimeoutMs,
            $timeoutMs,
            $throwExceptions
        );
        parent::__construct(
            $this->syncHandler,
            $bufferLimit,
            $level,
            $bubble,
            $flushOnOverflow
        );
        $this->setHighResolutionTimeOfLastFlush();
    }

    /**
     * @inheritDoc
     */
    public function handle(LogRecord $record): bool
    {
        $return = parent::handle($record);

        if ($this->highResolutionTimeOfNextFlush !== null && $this->highResolutionTimeOfNextFlush <= hrtime(true)) {
            $this->flush();
            $this->setHighResolutionTimeOfLastFlush();
        }

        return $return;
    }

    public function flush(): void
    {
        parent::flush();
        $this->setHighResolutionTimeOfLastFlush();
    }

    private function setHighResolutionTimeOfLastFlush(): void
    {
        if ($this->flushIntervalMs === null) {
            $this->highResolutionTimeOfNextFlush = null;
            return;
        }

        $this->highResolutionTimeOfNextFlush = hrtime(true) + $this->flushIntervalMs * 1e+6;
    }

    /**
     * @inheritDoc
     */
    public function setLevel(int|string|Level $level): self
    {
        parent::setLevel($level);
        $this->syncHandler->setLevel($level);
        return $this;
    }
}
