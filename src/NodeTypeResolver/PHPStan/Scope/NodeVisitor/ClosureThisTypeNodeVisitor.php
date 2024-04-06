<?php

namespace Rector\NodeTypeResolver\PHPStan\Scope\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorSelector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\PHPStan\ParametersAcceptorSelectorVariantsWrapper;
use Rector\NodeTypeResolver\PHPStan\Scope\Contract\NodeVisitor\ScopeResolverNodeVisitorInterface;
use Rector\Reflection\ReflectionResolver;

class ClosureThisTypeNodeVisitor extends NodeVisitorAbstract implements ScopeResolverNodeVisitorInterface
{
    private ReflectionResolver $reflectionResolver;
    private $scope;

    // used to avoid recursion
    public function autowire(ReflectionResolver $reflectionResolver)
    {
        $this->reflectionResolver = $reflectionResolver;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function enterNode(Node $node): ?Node
    {
        if (
            $node instanceof Node\Expr\StaticCall ||
            $node instanceof Node\Expr\MethodCall ||
            $node instanceof Node\Expr\FuncCall
        ) {
            $reflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($node);

            if ($reflection === null) {
                return null;
            }

            $parametersAcceptor = ParametersAcceptorSelectorVariantsWrapper::select($reflection, $node, $this->scope);
            $parameters = $parametersAcceptor->getParameters();

            foreach ($node->getArgs() as $index => $arg) {

                if (!$arg->value instanceof Closure) {
                    continue;
                }

                if ($arg->name?->name !== null) {
                    /** @var ParameterReflectionWithPhpDocs $parameter */
                    foreach ($parameters as $parameter) {
                        if ($parameter->getClosureThisType() !== null && $arg->name->name === $parameter->getName()) {
                            $arg->value->setAttribute('closureThisType', $parameter->getClosureThisType());
                        }
                    }

                    continue;
                }

                if ($arg->name?->name === null) {
                    /** @var ParameterReflectionWithPhpDocs $parameter */
                    $parameter = $parameters[$index] ?? null;

                    if ($parameter === null) {
                        continue;
                    }

                    if ($parameter->getClosureThisType() !== null) {
                        $arg->value->setAttribute('closureThisType', $parameter->getClosureThisType());
                    }
                }
            }

            return $node;
        }

        return null;
    }
}
