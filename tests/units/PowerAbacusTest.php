<?php
namespace GlpiPlugin\Carbon\Tests;

use PHPUnit\Framework\TestCase;

class PowerAbacusTest extends TestCase
{
    public function test1()
    {
        $formula = '$a * $b + $c';
        $variables = [
            'a' => 2,
            'b' => 3,
            'c' => 4,
        ];
        $powerAbacus = new PowerAbacus($formula);
        $output = $powerAbacus->evaluate($variables);
        $this->assertEquals(10, $output);
    }

    public function test2()
    {
        $formula = '$ram * 27';
        $variables = [
            'ram' => 2,
        ];
        $powerAbacus = new PowerAbacus($formula);
        $output = $powerAbacus->evaluate($variables);
        $this->assertEquals(54, $output);
    }
}
