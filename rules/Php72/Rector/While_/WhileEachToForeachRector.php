<?php

declare(strict_types=1);

namespace Rector\Php72\Rector\While_;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\While_;
use Rector\NodeManipulator\AssignManipulator;
use Rector\Rector\AbstractRector;
use Rector\ValueObject\PhpVersionFeature;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @changelog https://wiki.php.net/rfc/deprecations_php_7_2#each
 *
 * @see \Rector\Tests\Php72\Rector\While_\WhileEachToForeachRector\WhileEachToForeachRectorTest
 */
final class WhileEachToForeachRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(
        private readonly AssignManipulator $assignManipulator
    ) {
    }

    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATE_EACH;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'each() function is deprecated, use foreach() instead.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
while (list($key, $callback) = each($callbacks)) {
    // ...
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
foreach ($callbacks as $key => $callback) {
    // ...
}
CODE_SAMPLE
                ),
                new CodeSample(
                    <<<'CODE_SAMPLE'
while (list($key) = each($callbacks)) {
    // ...
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
foreach (array_keys($callbacks) as $key) {
    // ...
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
        return [While_::class];
    }

    /**
     * @param While_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $node->cond instanceof Assign) {
            return null;
        }

        /** @var Assign $assignNode */
        $assignNode = $node->cond;
        if (! $this->assignManipulator->isListToEachAssign($assignNode)) {
            return null;
        }

        /** @var FuncCall $eachFuncCall */
        $eachFuncCall = $assignNode->expr;

        if ($eachFuncCall->isFirstClassCallable()) {
            return null;
        }

        /** @var List_ $listNode */
        $listNode = $assignNode->var;

        if (! isset($eachFuncCall->getArgs()[0])) {
            return null;
        }

        $firstArg = $eachFuncCall->getArgs()[0];

        $foreachedExpr = count($listNode->items) === 1 ? $this->nodeFactory->createFuncCall(
            'array_keys',
            [$firstArg]
        ) : $firstArg->value;

        $arrayItem = array_pop($listNode->items);

        $isTrailingCommaLast = false;

        if (! $arrayItem instanceof ArrayItem) {
            $foreachedExpr = $this->nodeFactory->createFuncCall('array_keys', [$eachFuncCall->args[0]]);
            /** @var ArrayItem $arrayItem */
            $arrayItem = current($listNode->items);
            $isTrailingCommaLast = true;
        }

        $foreach = new Foreach_($foreachedExpr, $arrayItem, [
            'stmts' => $node->stmts,
        ]);

        $this->mirrorComments($foreach, $node);

        // is key included? add it to foreach
        if ($listNode->items !== []) {
            /** @var ArrayItem|null $keyItem */
            $keyItem = array_pop($listNode->items);

            if ($keyItem instanceof ArrayItem && ! $isTrailingCommaLast) {
                $foreach->keyVar = $keyItem->value;
            }
        }

        return $foreach;
    }
}
