<?php

namespace AcMarche\Bottin;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AcMarcheBottinBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
