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

class GraphNode
{
    public const PARAM_FIELDS = 'fields';
    public const PARAM_LIMIT = 'limit';

    protected string $name;
    protected array $modifiers = [];
    protected array $fields = [];
    protected array $compiledValues = [];

    public function __construct(string $name, array $fields = [], int $limit = 0)
    {
        $this->name = $name;

        $this->fields($fields);

        if ($limit > 0) {
            $this->limit($limit);
        }
    }

    public function modifiers(array $data): self
    {
        $this->modifiers = array_merge($this->modifiers, $data);

        return $this;
    }

    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    public function getModifier(string $key): mixed
    {
        return $this->modifiers[$key] ?? null;
    }

    public function limit(int $limit): self
    {
        return $this->modifiers([
            static::PARAM_LIMIT => $limit,
        ]);
    }

    public function getLimit(): ?int
    {
        return $this->getModifier(static::PARAM_LIMIT);
    }

    public function fields(mixed $fields): self
    {
        if (!is_array($fields)) {
            $fields = func_get_args();
        }

        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function resetCompiledValues(): void
    {
        $this->compiledValues = [];
    }

    public function compileModifiers(): void
    {
        if (count($this->modifiers) === 0) {
            return;
        }

        $this->compiledValues[] = http_build_query($this->modifiers, '', '&');
    }

    public function compileFields(): void
    {
        if (count($this->fields) === 0) {
            return;
        }

        $this->compiledValues[] = sprintf('%s=%s', static::PARAM_FIELDS, implode(',', $this->fields));
    }

    public function compileUrl(): string
    {
        $append = '';
        if (count($this->compiledValues) > 0) {
            $append = sprintf('?%s', implode('&', $this->compiledValues));
        }

        return sprintf('/%s%s', $this->name, $append);
    }

    public function asUrl(): string
    {
        $this->resetCompiledValues();
        $this->compileModifiers();
        $this->compileFields();

        return $this->compileUrl();
    }

    public function __toString(): string
    {
        return $this->asUrl();
    }
}
