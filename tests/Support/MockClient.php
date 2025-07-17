<?php

declare(strict_types=1);

namespace TomasKulhanek\Tests\Monolog\Loki\Support;

use TomasKulhanek\Monolog\Loki\LokiClient;

class MockClient extends LokiClient
{
    public mixed $capturedData = null;

    public function __construct()
    {
        parent::__construct('username', 'password', 'endpoint');
    }

    public function send($data): void
    {
        $this->capturedData = $data;
    }
}
