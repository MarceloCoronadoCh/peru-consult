<?php

declare(strict_types=1);

namespace Tests\Peru\Jne;

use Peru\Dni\DniFallback;
use Peru\Jne;
use Peru\Http\CurlClient;
use PHPUnit\Framework\TestCase;

class DniFactoryTest extends TestCase
{
    public function testCreate()
    {
        $factory = new Jne\DniFactory();

        $cs = $factory->create();

        $this->assertInstanceOf(DniFallback::class, $cs);
    }

    public function testCreateWithClient()
    {
        $factory = new Jne\DniFactory(new CurlClient());

        $cs = $factory->create();

        $this->assertInstanceOf(DniFallback::class, $cs);
    }
}