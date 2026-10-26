<?php

declare(strict_types=1);

namespace Peru\Eldni;

use Peru\Http\ClientInterface;
use Peru\Reniec\Person;
use Peru\Services\DniInterface;

/**
 * Consulta DNI alternativas a través de eldni.com:
 * 1. GET de la página para obtener el _token CSRF y la cookie de sesión.
 * 2. POST del formulario (token + dni) y parseo de la tabla de resultados.
 */
class Dni implements DniInterface
{
    private const TOKEN_PATTERN = '/name="_token"[^>]*value="([^"]+)"/';

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
     * Get Person Information by DNI.
     *
     * @param string $dni
     *
     * @return Person|null
     */
    public function get(string $dni): ?Person
    {
        $page = $this->client->get(Endpoints::SEARCH);

        if (!is_string($page) || $page === '' || !preg_match(self::TOKEN_PATTERN, $page, $matches)) {
            return null;
        }

        $html = $this->client->post(
            Endpoints::SEARCH,
            ['_token' => $matches[1], 'dni' => $dni],
            [
                'Origin'  => Endpoints::ORIGIN,
                'Referer' => Endpoints::SEARCH,
            ]
        );

        if (!is_string($html) || strpos($html, 'Resultados') === false) {
            return null;
        }

        return $this->parser->parse($dni, $html);
    }
}