<?php

namespace AcMarche\Bottin;

use AcMarche\Pivot\DependencyInjection\PivotContainer;

class Env
{
    public static function loadEnv(): void
    {
        PivotContainer::init();
    }
}
