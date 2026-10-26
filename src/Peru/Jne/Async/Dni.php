<?php

namespace Peru\Jne\Async;

use Peru\Http\Async\ClientInterface;
use Peru\Jne\DniParser;
use Peru\Jne\Endpoints;
use React\Promise\PromiseInterface;

class Dni
{
    const TIPO_MAYORES = '1';
    const TIPO_MENORES = '2';
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var DniParser
     */
    private $parser;

    /**
     * Dni constructor.
     *
     * @param ClientInterface $client
     * @param DniParser       $parser
     */
    public function __construct(ClientInterface $client, DniParser $parser)
    {
        $this->client = $client;
        $this->parser = $parser;
    }

    /**
     * Override JNE Request token
     *
     * @deprecated unused
     * @param string $requestToken
     */
    public function setRequestToken(string $requestToken): void
    {
    }

    /**
     * Get Person Information by DNI.
     *
     * @param string $dni
     *
     * @return PromiseInterface
     */
    public function get(string $dni): PromiseInterface
    {
        return $this->consult($dni, self::TIPO_MAYORES)
            ->then(function ($person) use ($dni) {
                if ($person !== null) {
                    return $person;
                }

                return $this->consult($dni, self::TIPO_MENORES);
            });
    }

    /**
     * Consulta el DNI contra SUNAT para un tipo de persona.
     *
     * @param string $dni
     * @param string $tipPers 1 mayores, 2 menores de edad
     *
     * @return PromiseInterface
     */
    private function consult(string $dni, string $tipPers): PromiseInterface
    {
        $payload = (string) json_encode([
            'tipDocu' => '1',
            'numDocu' => $dni,
            'tipPers' => $tipPers,
            'token'   => bin2hex(random_bytes(26)),
        ]);

        return $this->client
            ->postAsync(Endpoints::CONSULT, $payload, ['Content-Type' => 'application/json'])
            ->then(function ($json) use ($dni) {
                if ($json === false || !($result = json_decode($json)) || !isset($result->nombreSoli)) {
                    return null;
                }

                return $this->parser->parse($dni, $result);
            });
    }
}
