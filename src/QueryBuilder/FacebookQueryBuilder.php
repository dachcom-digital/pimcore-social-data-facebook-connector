<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace SocialData\Connector\Facebook\QueryBuilder;

final class FacebookQueryBuilder
{
    private GraphNode $graphNode;

    public function __construct(?string $graphEndpoint = '')
    {
        if (isset($graphEndpoint)) {
            $this->graphNode = new GraphNode($graphEndpoint);
        }
    }

    public function node(string $graphNodeName): self
    {
        return new self($graphNodeName);
    }

    public function edge(string $edgeName, array $fields = []): GraphEdge
    {
        return new GraphEdge($edgeName, $fields);
    }

    public function fields(mixed $fields): self
    {
        if (!is_array($fields)) {
            $fields = func_get_args();
        }

        $this->graphNode->fields($fields);

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->graphNode->limit($limit);

        return $this;
    }

    public function modifiers(array $data): self
    {
        $this->graphNode->modifiers($data);

        return $this;
    }

    public function asEndpoint(): string
    {
        return $this->graphNode->asUrl();
    }

    public function __toString(): string
    {
        return $this->asEndpoint();
    }
}
