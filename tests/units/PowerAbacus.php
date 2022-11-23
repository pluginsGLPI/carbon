<?php

namespace GlpiPlugin\Carbon\tests\units;

require_once __DIR__ . '/../../src/PowerAbacus.php';

use atoum;

class PowerAbacus extends atoum
{
    public function test1()
    {
        $this->function->error_log = true;

        $this->given(
            $formula = '$a * $b + $c',
            $variables = [
                'a' => 2,
                'b' => 3,
                'c' => 4,
            ],
            $this->newTestedInstance($formula)
        )
            ->then
            ->variable($this->testedInstance->evaluate($variables))
            ->isEqualTo(10);
    }

    public function test2()
    {
        $this->function->error_log = true;

        $this->given(
            $formula = '$ram * 27',
            $variables = [
                'ram' => 2,
            ],
            $this->newTestedInstance($formula)
        )
            ->then
            ->variable($this->testedInstance->evaluate($variables))
            ->isEqualTo(54);
    }
}
