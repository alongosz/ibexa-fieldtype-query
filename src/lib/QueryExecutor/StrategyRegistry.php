<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeQuery\QueryExecutor;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

/**
 * @internal
 */
final class StrategyRegistry implements QueryExecutorStrategyRegistryInterface
{
    /** @var iterable<\Ibexa\FieldTypeQuery\QueryExecutor\QueryExecutorStrategyInterface>  */
    private iterable $strategies;

    /**
     * @param iterable<\Ibexa\FieldTypeQuery\QueryExecutor\QueryExecutorStrategyInterface> $strategies
     */
    public function __construct(iterable $strategies)
    {
        $this->strategies = $strategies;
    }

    public function getStrategy(Query $query): QueryExecutorStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($query)) {
                return $strategy;
            }
        }

        throw new InvalidArgumentException('$query', 'There\'s no strategy to execute the given field type query');
    }
}
