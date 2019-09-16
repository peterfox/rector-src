<?php declare(strict_types=1);

namespace Rector\Laravel\Rector\Class_;

use PhpParser\Node;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;

/**
 * @see \Rector\Laravel\Tests\Rector\Class_\InlineValidationRulesToArrayDefinitionRector\InlineValidationRulesToArrayDefinitionRectorTest
 */
final class InlineValidationRulesToArrayDefinitionRector extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Transforms inline validation rules to array definition', [
            new CodeSample(
                <<<'CODE_SAMPLE'
use Illuminate\Foundation\Http\FormRequest;

class SomeClass extends FormRequest
{
    public function rules(): array
    {
        return [
            'someAttribute' => 'required|string|exists:' . SomeModel::class . 'id',
        ];
    }
}
CODE_SAMPLE
,
                <<<'CODE_SAMPLE'
use Illuminate\Foundation\Http\FormRequest;

class SomeClass extends FormRequest
{
    public function rules(): array
    {
        return [
            'someAttribute' => ['required', 'string', \Illuminate\Validation\Rule::exists(SomeModel::class, 'id')],
        ];
    }
}
CODE_SAMPLE

            )
        ]);
    }

    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class];
    }

    /**
     * @param Node\Stmt\Class_ $node
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->isObjectType($node, 'Illuminate\Foundation\Http\FormRequest') === false) {
            return null;
        }

        $rules = $node->getMethod('rules');
        if ($rules === null || $rules->stmts === null) {
            return null;
        }

        foreach ($rules->stmts as $statement) {
            if (!$statement instanceof Node\Stmt\Return_) {
                continue;
            }

            $expression = $statement->expr;
            if (!$expression instanceof Node\Expr\Array_) {
                continue;
            }

            foreach ($expression->items as $item) {
                if (!$item->value instanceof Node\Scalar\String_ && !$item->value instanceof Node\Expr\BinaryOp\Concat) {
                    continue;
                }

                $newRules = $this->transformRulesSetToExpressionsArray($item->value);
                foreach ($newRules as &$newRule) {
                    if (!($newRule instanceof Node\Expr\BinaryOp\Concat)) {
                        continue;
                    }

                    try {
                        $fullString = $this->transformConcatExpressionToSingleString($newRule);
                    } catch (\LogicException $exception) {
                        continue;
                    }

                    $matchesExist = preg_match('/^exists:(\w+),(\w+)$/', $fullString, $matches);
                    if ($matchesExist === false || $matchesExist === 0) {
                        continue;
                    }

                    $ruleClass = $matches[1];
                    $ruleAttribute = $matches[2];

                    $newRule = new Node\Expr\StaticCall(
                        new Node\Name('\Illuminate\Validation\Rule'),
                        'exists',
                        [
                            new Node\Arg(
                                new Node\Expr\ClassConstFetch(
                                    new Node\Name($ruleClass),
                                    'class'
                                )
                            ),
                            new Node\Arg(new Node\Scalar\String_($ruleAttribute)),
                        ]
                    );
                }

                $item->value = $this->createArray($newRules);
            }
        }

        return $node;
    }

    /**
     * @param Node\Expr $expr
     * @return Node\Expr[]
     */
    private function transformRulesSetToExpressionsArray(Node\Expr $expr): array
    {
        if ($expr instanceof Node\Scalar\String_) {
            return array_map(static function (string $value): Node\Scalar\String_ {
                return new Node\Scalar\String_($value);
            }, explode('|', $expr->value));
        } elseif ($expr instanceof Node\Expr\BinaryOp\Concat) {
            $left = $this->transformRulesSetToExpressionsArray($expr->left);
            $expr->left = $left[count($left ) - 1];

            $right = $this->transformRulesSetToExpressionsArray($expr->right);
            $expr->right = $right [0];

            return array_merge(array_slice($left, 0, -1), [$expr], array_slice($right, 1));
        } elseif ($expr instanceof Node\Expr\ClassConstFetch
            || $expr instanceof Node\Expr\MethodCall
            || $expr instanceof Node\Expr\FuncCall
        ) {
            return [$expr];
        }

        throw new \InvalidArgumentException('Unexpected call ' . get_class($expr));
    }

    private function transformConcatExpressionToSingleString(Node\Expr\BinaryOp\Concat $expression): string
    {
        $output = '';

        foreach ([$expression->left, $expression->right] as $expressionPart) {
            if ($expressionPart instanceof Node\Scalar\String_) {
                $output .= $expressionPart->value;
            } elseif ($expressionPart instanceof Node\Expr\BinaryOp\Concat) {
                $output .= $this->transformConcatExpressionToSingleString($expressionPart);
            } elseif ($expressionPart instanceof Node\Expr\ClassConstFetch) {
                /** @var Node\Name $name */
                $name = $expressionPart->class->getAttribute('originalName');

                $output .= implode('\\', $name->parts);
            } else {
                throw new \LogicException('Unexpected expression type ' . get_class($expressionPart));
            }
        }

        return $output;
    }
}
