<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 15/11/2017
 * Time: 04:15 PM.
 */

namespace Peru\Sunat;

use Peru\Http\ClientInterface;
use Peru\Services\RucInterface;

/**
 * Class Ruc.
 */
class Ruc implements RucInterface
{
    use RandomTrait;

    /**
     * @var ClientInterface
     */
    public $client;
    /**
     * @var RucParser
     */
    private $parser;
    /**
     * @var Retention
     */
    private $retention;

    /**
     * Ruc constructor.
     *
     * @param ClientInterface $client
     * @param RucParser       $parser
     */
    public function __construct(ClientInterface $client, RucParser $parser)
    {
        $this->client = $client;
        $this->parser = $parser;
    }

    /**
     * Set Retention service.
     *
     * @param Retention $retention
     */
    public function setRetention(Retention $retention)
    {
        $this->retention = $retention;
    }

    /**
     * Get Company Information by RUC.
     *
     * @param string $ruc
     *
     * @return null|Company
     */
    public function get(string $ruc): ?Company
    {
        $this->client->get(Endpoints::CONSULT);
        $htmlRandom = $this->client->post(Endpoints::CONSULT, [
            'accion' => 'consPorRazonSoc',
            'razSoc' => 'BVA FOODS',
        ]);

        $random = $this->getRandom($htmlRandom);

        $html = $this->client->post(Endpoints::CONSULT, [
            'accion' => 'consPorRuc',
            'nroRuc' => $ruc,
            'numRnd' => $random,
            'actReturn' => '1',
            'modo' => '1',
        ]);

        $company = $html === false ? null : $this->parser->parse($html);
        if ($company !== null && $this->retention !== null) {
            $company->retencion = $this->retention->get($ruc);
        }

        return $company;
    }
}
