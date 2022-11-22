<?php

namespace GlpiPlugin\Carbon\tests\units;

require_once __DIR__ . '/../../src/PowerAbacus.php';

use atoum;

class PowerAbacus extends atoum
{
    public function testEvaluate()
    {
        $abacus = new \GlpiPlugin\Carbon\PowerAbacus('$power * 1.5');
        $this->string($abacus->evaluate(['power' => 100]))->isEqualTo('150');
    }
}
