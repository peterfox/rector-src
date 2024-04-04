<?php

declare(strict_types=1);

namespace Rector\CodingStyle\Rector\Closure;

use PhpParser\Builder\FunctionLike;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use Rector\CodingStyle\Guard\StaticGuard;
use Rector\NodeTypeResolver\PHPStan\ParametersAcceptorSelectorVariantsWrapper;
use Rector\Rector\AbstractRector;
use Rector\Reflection\MethodReflectionResolver;
use Rector\Reflection\ReflectionResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\StaticClosureRectorTest
 */
final class StaticClosureRector extends AbstractRector
{
    public function __construct(
        private readonly StaticGuard $staticGuard,
        private readonly ReflectionResolver $reflectionResolver
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Changes Closure to be static when possible',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
function () {
    if (rand(0, 1)) {
        return 1;
    }

    return 2;
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
static function () {
    if (rand(0, 1)) {
        return 1;
    }

    return 2;
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Closure::class, Node\Expr\StaticCall::class, Node\Expr\MethodCall::class, Node\Expr\FuncCall::class];
    }

    /**
     * @param Closure|Node\Expr\StaticCall|Node\Expr\MethodCall|Node\Expr\FuncCall $node
     */
    public function refactor(Node $node): Node|int|null
    {
        if (! $node instanceof Closure) {

            $reflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($node);

            if (! $reflection) {
                return null;
            }

            if (! $scope = $node->getAttribute('scope')) {
                return null;
            }

            $parametersAcceptor = ParametersAcceptorSelectorVariantsWrapper::select(
                $reflection,
                $node,
                $scope,
            );

            $parameterIndex = [];
            $parameterNames = [];

            foreach ($parametersAcceptor->getParameters() as $index => $parameter) {
                if ($parameter instanceof ParameterReflectionWithPhpDocs && $parameter->getClosureThisType() !== null) {
                    $parameterIndex[$index] = $parameter->getClosureThisType();
                    $parameterNames[$parameter->getName()] = $parameter->getClosureThisType();
                }
            }

            foreach ($node->getArgs() as $index => $arg) {

                if (!$arg->value instanceof Closure) {
                    continue;
                }

                if (
                    ($arg->name?->name !== null && array_key_exists($arg->name->toString(), $parameterNames)) ||
                    ($arg->name?->name === null && array_key_exists($index, $parameterIndex))
                ) {
                    $arg->setAttribute('closureThisType', true);
                    $arg->value->setAttribute('closureThisType', true);
                }
            }

            return null;
        }

        if ($node->hasAttribute('closureThisType')) {
            return null;
        }

        if (! $this->staticGuard->isLegal($node)) {
            return null;
        }

        $node->static = true;

        return $node;
    }
}
