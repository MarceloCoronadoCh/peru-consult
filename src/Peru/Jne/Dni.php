<?php
/**
 * Created by PhpStorm.
 * User: Soporte
 * Date: 24/09/2018
 * Time: 11:25.
 */

namespace Peru\Jne;

use Peru\Http\ClientInterface;
use Peru\Reniec\Person;
use Peru\Services\DniInterface;
/**
 * Class Dni.
 */
class Dni implements DniInterface
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
     * @deprecated
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
     * @return Person|null
     */
    public function get(string $dni): ?Person
    {
        foreach ([self::TIPO_MAYORES, self::TIPO_MENORES] as $tipPers) {
            $json = $this->client->post(
                Endpoints::CONSULT,
                $this->buildPayload($dni, $tipPers),
                ['Content-Type' => 'application/json']
            );

            if ($json === false || !($result = json_decode($json)) || !isset($result->nombreSoli)) {
                continue;
            }

            return $this->parser->parse($dni, $result);
        }

        return null;
    }

    /**
     * Payload de consulta. SUNAT exige el campo "token" no vacío
     * (no valida su valor), se genera uno aleatorio por consulta.
     *
     * @param string $dni
     * @param string $tipPers 1 mayores, 2 menores de edad
     *
     * @return string
     */
    private function buildPayload(string $dni, string $tipPers): string
    {
        return (string) json_encode([
            'tipDocu' => '1',
            'numDocu' => $dni,
            'tipPers' => $tipPers,
            'token'   => bin2hex(random_bytes(26)),
        ]);
    }
}
