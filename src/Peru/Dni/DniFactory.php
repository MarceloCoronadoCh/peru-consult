<?php

declare(strict_types=1);

namespace Peru\Dni;

use Peru\Eldni\Dni as EldniDni;
use Peru\Eldni\DniParser as EldniDniParser;
use Peru\Http\ClientInterface;
use Peru\Http\CurlClient;
use Peru\Jne\Dni;
use Peru\Jne\DniParser;
use Peru\Services\DniInterface;

/**
 * Fábrica de la consulta DNI con Fallback: SUNAT -> elDni.
 */
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
        $client = $this->client ?? new CurlClient();

        return new DniFallback([
            new Dni($client, new DniParser()),
            new EldniDni($client, new EldniDniParser()),
        ]);
    }
}