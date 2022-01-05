<?php

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

    public function node(string $graphNodeName): FacebookQueryBuilder
    {
        return new FacebookQueryBuilder($graphNodeName);
    }

    public function edge(string $edgeName, array $fields = []): GraphEdge
    {
        return new GraphEdge($edgeName, $fields);
    }

    public function fields(mixed $fields): FacebookQueryBuilder
    {
        if (!is_array($fields)) {
            $fields = func_get_args();
        }

        $this->graphNode->fields($fields);

        return $this;
    }

    public function limit(int $limit): FacebookQueryBuilder
    {
        $this->graphNode->limit($limit);

        return $this;
    }

    public function modifiers(array $data): FacebookQueryBuilder
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
