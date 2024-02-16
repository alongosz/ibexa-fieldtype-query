<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\FieldTypeQuery;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\FieldTypeQuery\QueryFieldLocationService;
use Ibexa\Contracts\FieldTypeQuery\QueryFieldServiceInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\QueryType\QueryTypeRegistry;
use Ibexa\FieldTypeQuery\QueryExecutor\QueryExecutorStrategyRegistryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Executes a query and returns the results.
 */
final class QueryFieldService implements QueryFieldServiceInterface, QueryFieldLocationService
{
    private QueryTypeRegistry $queryTypeRegistry;

    private QueryExecutorStrategyRegistryInterface $queryExecutorStrategyRegistry;

    public function __construct(
        QueryTypeRegistry $queryTypeRegistry,
        QueryExecutorStrategyRegistryInterface $queryExecutorStrategyRegistry
    ) {
        $this->queryTypeRegistry = $queryTypeRegistry;
        $this->queryExecutorStrategyRegistry = $queryExecutorStrategyRegistry;
    }

    public function loadContentItems(Content $content, string $fieldDefinitionIdentifier): iterable
    {
        $query = $this->prepareQuery($content, $content->contentInfo->getMainLocation(), $fieldDefinitionIdentifier);

        return $this->queryExecutorStrategyRegistry->getStrategy($query)->findContentItems($query);
    }

    public function loadContentItemsForLocation(Location $location, string $fieldDefinitionIdentifier): iterable
    {
        $query = $this->prepareQuery($location->getContent(), $location, $fieldDefinitionIdentifier);

        return $this->queryExecutorStrategyRegistry->getStrategy($query)->findContentItems($query);
    }

    public function countContentItems(Content $content, string $fieldDefinitionIdentifier): int
    {
        $query = $this->prepareQuery($content, $content->contentInfo->getMainLocation(), $fieldDefinitionIdentifier);
        $query->limit = 0;

        return $this->countContentItemsForQuery($query);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function countContentItemsForQuery(Query $query): int
    {
        return max(
            $this->queryExecutorStrategyRegistry->getStrategy($query)->countContentItems($query) - $query->offset,
            0
        );
    }

    public function countContentItemsForLocation(Location $location, string $fieldDefinitionIdentifier): int
    {
        $query = $this->prepareQuery($location->getContent(), $location, $fieldDefinitionIdentifier);
        $query->limit = 0;

        return $this->countContentItemsForQuery($query);
    }

    public function loadContentItemsSlice(Content $content, string $fieldDefinitionIdentifier, int $offset, int $limit): iterable
    {
        $query = $this->prepareQuery($content, $content->contentInfo->getMainLocation(), $fieldDefinitionIdentifier);
        $query->offset += $offset;
        $query->limit = $limit;

        return $this->queryExecutorStrategyRegistry->getStrategy($query)->findContentItems($query);
    }

    public function loadContentItemsSliceForLocation(Location $location, string $fieldDefinitionIdentifier, int $offset, int $limit): iterable
    {
        $query = $this->prepareQuery($location->getContent(), $location, $fieldDefinitionIdentifier);
        $query->offset += $offset;
        $query->limit = $limit;

        return $this->queryExecutorStrategyRegistry->getStrategy($query)->findContentItems($query);
    }

    public function getPaginationConfiguration(Content $content, string $fieldDefinitionIdentifier): int
    {
        $fieldDefinition = $this->loadFieldDefinition($content, $fieldDefinitionIdentifier);

        if ($fieldDefinition->fieldSettings['EnablePagination'] === false) {
            return 0;
        }

        return $fieldDefinition->fieldSettings['ItemsPerPage'];
    }

    /**
     * @param array $expressions parameters that may include expressions to be resolved
     */
    private function resolveParameters(array $expressions, array $variables): array
    {
        foreach ($expressions as $key => $expression) {
            if (is_array($expression)) {
                $expressions[$key] = $this->resolveParameters($expression, $variables);
            } elseif ($this->isExpression($expression)) {
                $expressions[$key] = $this->resolveExpression($expression, $variables);
            } else {
                $expressions[$key] = $expression;
            }
        }

        return $expressions;
    }

    private function isExpression($expression): bool
    {
        return is_string($expression) && substr($expression, 0, 2) === '@=';
    }

    /**
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException if $expression is not an expression.
     */
    private function resolveExpression(string $expression, array $variables)
    {
        if (!$this->isExpression($expression)) {
            throw new InvalidArgumentException('expression', 'is not an expression');
        }

        return (new ExpressionLanguage())->evaluate(substr($expression, 2), $variables);
    }

    private function prepareQuery(Content $content, Location $location, string $fieldDefinitionIdentifier, array $extraParameters = []): Query
    {
        $fieldDefinition = $this->loadFieldDefinition($content, $fieldDefinitionIdentifier);

        $queryType = $this->queryTypeRegistry->getQueryType($fieldDefinition->fieldSettings['QueryType']);
        $parameters = $this->resolveParameters(
            $fieldDefinition->fieldSettings['Parameters'],
            array_merge(
                $extraParameters,
                [
                    'content' => $content,
                    'contentInfo' => $content->contentInfo,
                    'location' => $location,
                    'mainLocation' => $location->id === $content->contentInfo->mainLocationId ? $location : $content->contentInfo->getMainLocation(),
                    'returnedType' => $fieldDefinition->fieldSettings['ReturnedType'],
                ]
            )
        );

        return $queryType->getQuery($parameters);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function loadFieldDefinition(Content $content, string $fieldDefinitionIdentifier): FieldDefinition
    {
        $contentType = $content->getContentType();
        $fieldDefinition = $contentType->getFieldDefinition($fieldDefinitionIdentifier);

        if ($fieldDefinition === null) {
            throw new NotFoundException(
                'Query field definition',
                $contentType->identifier . '/' . $fieldDefinitionIdentifier
            );
        }

        return $fieldDefinition;
    }
}

class_alias(QueryFieldService::class, 'EzSystems\EzPlatformQueryFieldType\API\QueryFieldService');
