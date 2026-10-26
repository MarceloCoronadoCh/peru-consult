<?php

declare(strict_types=1);

namespace Tests\Peru\Eldni;

use Peru\Eldni\Dni;
use Peru\Eldni\DniParser;
use Peru\Http\ClientInterface;
use PHPUnit\Framework\TestCase;

class DniTest extends TestCase
{
    const SEARCH_PAGE = '<!DOCTYPE html><html><body>'
        .'<input type="hidden" name="_token" value="t0k3n-csRf">'
        .'</body></html>';

    const RESULT_PAGE = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div class="panel-heading">Resultados</div>'
        .'<table><tbody>'
        .'<tr><td>00000012</td><td>RICARDO</td><td>YAICATE</td><td>TARICUARIMA</td></tr>'
        .'</tbody></table>';

    /**
     * @return ClientInterface
     */
    private function createClientStub()
    {
        $stub = $this->getMockBuilder(ClientInterface::class)->getMock();
        $stub->method('get')->willReturn(self::SEARCH_PAGE);
        $stub->method('post')->willReturn(self::RESULT_PAGE);

        return $stub;
    }

    public function testGetDni()
    {
        $cs = new Dni($this->createClientStub(), new DniParser());

        $person = $cs->get('00000012');

        $this->assertNotNull($person);
        $this->assertEquals('00000012', $person->dni);
        $this->assertEquals('RICARDO', $person->nombres);
        $this->assertEquals('YAICATE', $person->apellidoPaterno);
        $this->assertEquals('TARICUARIMA', $person->apellidoMaterno);
        $this->assertEquals('0', $person->codVerifica);
    }

    public function testMissingCsrfToken()
    {
        $stub = $this->getMockBuilder(ClientInterface::class)->getMock();
        $stub->method('get')->willReturn('<html><body>no token</body></html>');
        /**@var $stub ClientInterface */
        $cs = new Dni($stub, new DniParser());

        $person = $cs->get('00000012');

        $this->assertNull($person);
    }

    public function testEmptyResults()
    {
        $stub = $this->getMockBuilder(ClientInterface::class)->getMock();
        $stub->method('get')->willReturn(self::SEARCH_PAGE);
        $stub->method('post')->willReturn('<div>No se encontraron Resultados</div>');
        /**@var $stub ClientInterface */
        $cs = new Dni($stub, new DniParser());

        $person = $cs->get('00000012');

        $this->assertNull($person);
    }
}