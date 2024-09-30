<?php

namespace Rector\TypeDeclaration\Rector\FunctionLike;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Param;
use PhpParser\Node\VariadicPlaceholder;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\TypeComparator\TypeComparator;
use Rector\PHPStanStaticTypeMapper\Enum\TypeKind;
use Rector\PHPStanStaticTypeMapper\Utils\TypeUnwrapper;
use Rector\Rector\AbstractRector;
use PHPStan\Type\ObjectType;
use PHPStan\Type\CallableType;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\Type;
use PHPStan\Reflection\ParameterReflection;
use Rector\Reflection\MethodReflectionResolver;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use function PHPStan\dumpType;

final class AddClosureParamTypeFromIterableMethodCallRector extends AbstractRector
{
    public function __construct(
        private readonly TypeComparator $typeComparator,
        private readonly StaticTypeMapper $staticTypeMapper,
        private readonly MethodReflectionResolver $methodReflectionResolver,
        private readonly TypeUnwrapper $typeUnwrapper,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            '',
            [
                new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @param Collection<string> $string
     */
    public function run(Collection $collection)
    {
        return $collection->map(function ($item) {
            return $item;
        });
    }
}
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @param Collection<string> $string
     */
    public function run(Collection $collection)
    {
        return $collection->map(function (string $item) {
            return $item;
        });
    }
}
CODE_SAMPLE,
)
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Node\Expr\MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        $varType = $this->getType($node->var);

        if (! $varType instanceof IntersectionType || ! $varType->isIterable()->yes()) {
            return null;
        }

        if ($node->isFirstClassCallable()) {
            return null;
        }

        $className = $varType->getObjectClassNames()[0] ?? null;

        if ($className === null) {
            return null;
        }

        $methodReflection = $this->methodReflectionResolver->resolveMethodReflection(
            $className,
            $node->name,
            $node->getAttribute(AttributeKey::SCOPE),
        );

        $parameters = $methodReflection->getVariants()[0]->getParameters();

        if (! $this->methodSignatureUsesCallableWithIteratorTypes($className, $parameters)) {
            return null;
        }

        if (! $this->callUsesClosures($node->getArgs())) {
            return null;
        }

        $nameIndex = [];
        foreach ($parameters as $index => $parameter) {
            $nameIndex[$parameter->getName()] = $index;
        }

        $valueType = $varType->getIterableValueType();
        $keyType = $varType->getIterableKeyType();
        var_dump([$valueType, $keyType]);

        foreach ($node->getArgs() as $index => $arg) {
            if (! $arg instanceof Arg && ! $arg->value instanceof Closure) {
                continue;
            }

            $parameter = (is_string($index) ? $nameIndex[$index] : $parameters[$index]) ?? null;

            $this->updateClosureWithTypes($parameter, $arg->value, $keyType, $valueType);
        }

        return null;
    }

    private function updateClosureWithTypes(ParameterReflection $parameter, Closure $closure, Type $keyType, Type $valueType)
    {

    }

    private function refactorParameter(Param $param, Type $type): bool
    {
        // already set → no change
        if ($param->type instanceof Node) {
            $currentParamType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($param->type);
            if ($this->typeComparator->areTypesEqual($currentParamType, $type)) {
                return false;
            }
        }

        $paramTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($type, TypeKind::PARAM);
        $param->type = $paramTypeNode;

        return true;
    }

    /**
     * @param class-string $className
     * @param ParameterReflection[] $parameters
     */
    private function methodSignatureUsesCallableWithIteratorTypes(string $className, array $parameters): bool
    {
        foreach ($parameters as $parameter) {
            $callableType = $this->typeUnwrapper->unwrapFirstCallableTypeFromUnionType($parameter->getType());

            if (! $callableType instanceof CallableType) {
                continue;
            }

            foreach ($callableType->getParameters() as $parameterReflection) {
                if (
                    $this->typeUnwrapper->isIterableTypeValue($className, $parameterReflection->getType()) ||
                    $this->typeUnwrapper->isIterableTypeKey($className, $parameterReflection->getType())
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<Arg|VariadicPlaceholder> $args
     */
    private function callUsesClosures(array $args): bool
    {
        foreach ($args as $arg) {
            if ($arg instanceof Arg && $arg->value instanceof Closure) {
                return true;
            }
        }

        return false;
    }
}
