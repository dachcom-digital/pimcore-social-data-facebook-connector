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

class GraphEdge extends GraphNode
{
    public function toEndpoints(): array
    {
        $endpoints = [];

        $children = $this->getChildEdges();
        foreach ($children as $child) {
            $endpoints[] = sprintf('/%s', implode('/', $child));
        }

        return $endpoints;
    }

    public function getChildEdges(): array
    {
        $edges = [];
        $hasChildren = false;

        foreach ($this->fields as $v) {
            if ($v instanceof self) {
                $hasChildren = true;

                $children = $v->getChildEdges();
                foreach ($children as $childEdges) {
                    $edges[] = array_merge([$this->name], $childEdges);
                }
            }
        }

        if (!$hasChildren) {
            $edges[] = [$this->name];
        }

        return $edges;
    }

    public function compileModifiers(): void
    {
        if (count($this->modifiers) === 0) {
            return;
        }

        $processed_modifiers = [];

        foreach ($this->modifiers as $k => $v) {
            $processed_modifiers[] = sprintf('%s(%s)', urlencode($k), urlencode($v));
        }

        $this->compiledValues[] = sprintf('.%s', implode('.', $processed_modifiers));
    }

    public function compileFields(): void
    {
        if (count($this->fields) === 0) {
            return;
        }

        $processed_fields = [];

        foreach ($this->fields as $v) {
            $processed_fields[] = $v instanceof self ? $v->asUrl() : urlencode($v);
        }

        $this->compiledValues[] = sprintf('{%s}', implode(',', $processed_fields));
    }

    public function compileUrl(): string
    {
        $append = '';

        if (count($this->compiledValues) > 0) {
            $append = implode('', $this->compiledValues);
        }

        return sprintf('%s%s', $this->name, $append);
    }
}
