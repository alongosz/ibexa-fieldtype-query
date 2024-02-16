<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeQuery\QueryExecutor\Strategy;

use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\FieldTypeQuery\QueryExecutor\QueryExecutorStrategyInterface;
use Webmozart\Assert\Assert;

final class ContentQueryExecutorStrategy implements QueryExecutorStrategyInterface
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function supports(Query $query): bool
    {
        return $query instanceof Query::class && !$query instanceof LocationQuery;
    }

    public function findContentItems(Query $query): iterable
    {
        return array_map(
            static function (SearchHit $searchHit): Content {
                /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $content */
                $content = $searchHit->valueObject;
                Assert::isInstanceOf($content, Content::class);

                return $content;
            },
            $this->searchService->findContent($query)->searchHits
        );
    }

    public function countContentItems(Query $query): int
    {
        return $this->searchService->findContent($query)->totalCount;
    }
}
