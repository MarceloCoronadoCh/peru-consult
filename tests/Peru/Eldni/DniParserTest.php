<?php

declare(strict_types=1);

namespace Tests\Peru\Eldni;

use Peru\Eldni\DniParser;
use PHPUnit\Framework\TestCase;

class DniParserTest extends TestCase
{
    public function testParseResultsTable()
    {
        $html = '<html><head><meta charset="utf-8"></head><body><div>Resultados</div><table><tbody>'
            .'<tr><td>46658592</td><td>LESLY LICET</td><td>PEREZ</td><td>PEÑA</td></tr>'
            .'</tbody></table></body></html>';

        $person = (new \Peru\Eldni\DniParser())->parse('46658592', $html);

        $this->assertNotNull($person);
        $this->assertEquals('46658592', $person->dni);
        $this->assertEquals('LESLY LICET', $person->nombres);
        $this->assertEquals('PEREZ', $person->apellidoPaterno);
        $this->assertEquals('PEÑA', $person->apellidoMaterno);
        $this->assertNotEmpty($person->codVerifica);
    }

    public function testParseSkipsEmptyRows()
    {
        $html = '<html><body><table><tbody>'
            .'<tr><td></td><td></td><td></td><td></td></tr>'
            .'<tr><td>00000004</td><td>JOYCE</td><td>BARDALES</td><td>TORRES</td></tr>'
            .'</tbody></table></body></html>';

        $person = (new \Peru\Eldni\DniParser())->parse('00000004', $html);

        $this->assertNotNull($person);
        $this->assertEquals('JOYCE', $person->nombres);
        $this->assertEquals('BARDALES', $person->apellidoPaterno);
    }

    public function testParseNoResults()
    {
        $html = '<html><body><p>Sin Resultados para el dni</p></body></html>';

        $person = (new \Peru\Eldni\DniParser())->parse('00000012', $html);

        $this->assertNull($person);
    }

    public function testCalculateVerifyCode()
    {
        // valor oficial esperado para 46658592
        $html = '<table><tbody>'
            .'<tr><td>46658592</td><td>LESLY LICET</td><td>PEREZ</td><td>PEÑA</td></tr>'
            .'</tbody></table>';

        $person = (new \Peru\Eldni\DniParser())->parse('46658592', $html);

        $this->assertNotNull($person);
        $this->assertEquals('6', $person->codVerifica);
    }
}