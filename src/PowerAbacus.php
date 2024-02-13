<?php

namespace GlpiPlugin\Carbon;

use Xylemical\Expressions\Math\BcMath;
use Xylemical\Expressions\Context;
use Xylemical\Expressions\ExpressionFactory;
use Xylemical\Expressions\Evaluator;
use Xylemical\Expressions\Lexer;
use Xylemical\Expressions\Parser;
use Xylemical\Expressions\Token;
use Xylemical\Expressions\Value;

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
