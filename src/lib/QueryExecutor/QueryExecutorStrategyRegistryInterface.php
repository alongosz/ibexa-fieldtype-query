<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeQuery\QueryExecutor;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;

/**
 * @internal
 */
interface QueryExecutorStrategyRegistryInterface
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getStrategy(Query $query): QueryExecutorStrategyInterface;
}
