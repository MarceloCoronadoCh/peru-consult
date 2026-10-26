<?php

declare(strict_types=1);

namespace Peru\Jne;

use Peru\Dni\DniFactory as DniFallbackFactory;
use Peru\Http\ClientInterface;
use Peru\Http\CurlClient;
use Peru\Services\DniInterface;

class DniFactory
{
    /**
     * @var ClientInterface|null
     */
    private $client;

    /**
     * DniFactory constructor.
     *
     * @param ClientInterface|null $client cliente compartido, null usa CurlClient
     */
    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client;
    }

    public function create(): DniInterface
    {
        return (new DniFallbackFactory($this->client))->create();
    }
}