<?php

declare(strict_types=1);

namespace Tests\Peru\Dni;

use Peru\Dni\DniFallback;
use Peru\Reniec\Person;
use Peru\Services\DniInterface;
use PHPUnit\Framework\TestCase;

class DniFallbackTest extends TestCase
{
    /**
     * @return DniInterface
     */
    private function createProviderStub()
    {
        return $this->getMockBuilder(DniInterface::class)->getMock();
    }

    /**
     * @return Person
     */
    private function createPerson()
    {
        $person = new Person();
        $person->dni = '46658592';
        $person->nombres = 'LESLY LICET';
        $person->apellidoPaterno = 'PEREZ';
        $person->apellidoMaterno = 'PEÑA';

        return $person;
    }

    public function testReturnsFirstProviderValue()
    {
        $person = $this->createPerson();
        $first = $this->createProviderStub();
        $second = $this->createProviderStub();
        $first->expects($this->once())->method('get')->willReturn($person);
        $second->expects($this->never())->method('get');

        $fallback = new DniFallback([$first, $second]);

        $result = $fallback->get('46658592');

        $this->assertSame($person, $result);
    }

    public function testFallsBackToSecondProvider()
    {
        $person = $this->createPerson();
        $first = $this->createProviderStub();
        $second = $this->createProviderStub();
        $first->expects($this->once())->method('get')->willReturn(null);
        $second->expects($this->once())->method('get')->willReturn($person);

        $fallback = new DniFallback([$first, $second]);

        $result = $fallback->get('46658592');

        $this->assertSame($person, $result);
    }

    public function testReturnsNullWhenAllProvidersFail()
    {
        $first = $this->createProviderStub();
        $second = $this->createProviderStub();
        $first->method('get')->willReturn(null);
        $second->method('get')->willReturn(null);

        $fallback = new DniFallback([$first, $second]);

        $result = $fallback->get('46658592');

        $this->assertNull($result);
    }
}