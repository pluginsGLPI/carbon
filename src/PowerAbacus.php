<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 Teclib' and contributors.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon;

use Xylemical\Expressions\Math\BcMath;
use Xylemical\Expressions\Context;
use Xylemical\Expressions\ExpressionFactory;
use Xylemical\Expressions\Evaluator;
use Xylemical\Expressions\Lexer;
use Xylemical\Expressions\Parser;
use Xylemical\Expressions\Token;
use Xylemical\Expressions\Value;

/** 
 * @deprecated was a tentative to express emission computation as formulas
 */
class PowerAbacus
{
    protected $tokens = null;

    public function __construct(string $formula)
    {
        $math = new BcMath();
        $factory = new ExpressionFactory($math);
        $factory->addOperator(
            new Value(
                '\$[a-zA-Z_][a-zA-Z0-9_]*',
                function (array $operands, Context $context, Token $token) {
                    return $context->getVariable(substr($token->getValue(), 1));
                }
            )
        );
        $lexer = new Lexer($factory);
        $parser = new Parser($lexer);

        $this->tokens = $parser->parse($formula);
    }

    public function evaluate(array $variables)
    {
        $context = new Context($variables);
        $evaluator = new Evaluator();

        return $evaluator->evaluate($this->tokens, $context);
    }
}
