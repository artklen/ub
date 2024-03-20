<?php

class UB implements ArrayAccess, IteratorAggregate
{
    public function __construct(
        protected string $path = '',
        protected array $fields = [],
        protected array $values = [],
    ) {
    }

    public function __toString()
    {
        if (empty($this->values)) {
            return $this->path;
        }

        $values = $this->values;
        $params = [];

        foreach ($this->fields as $name) {
            if (isset($values[$name])) {
                $params[$name] = $values[$name];
                unset($values[$name]);
            }
        }

        foreach ($values as $name => $value) {
            $params[$name] = $value;
        }

        $query = $this->buildQueryRecursive($params);
        if ($query === '') {
            return $this->path;
        }
        return $this->path . '?' . $query;
    }

    protected function buildQueryRecursive(array $params, $parent = null): string
    {
        $anonymousNumericKey = 0;
        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }

            $name = $key;
            if (is_numeric($key)) {
                if ($key === $anonymousNumericKey) {
                    $name = '';
                }
                $anonymousNumericKey = $key + 1;
            }

            $name = $this->urlencode($name);

            if (isset($parent)) {
                $name = $parent . '%5B' . $name . '%5D';
            }

            if (is_array($value)) {
                $part = $this->buildQueryRecursive($value, $name);
                if ($part !== '') {
                    $parts[] = $part;
                }
            } else {
                $parts[] = $name . '=' . $this->urlencode((string) $value);
            }
        }

        return implode('&', $parts);
    }

    protected function urlencode(string $string): string
    {
        return preg_replace_callback(
            '/\W/u',
            static function ($matches) {
                return urlencode($matches[0]);
            },
            $string
        );
    }

    public function withPath(string $path): static
    {
        $copy = clone $this;
        $copy->path = $path;
        return $copy;
    }

    public function with(string $name, $value): static
    {
        $copy = clone $this;

        if ($value !== null) {
            $copy->values[$name] = $value;
        } else {
            unset($copy->values[$name]);
        }

        return $copy;
    }

    public function without(string $name): static
    {
        $copy = clone $this;
        unset($copy->values[$name]);
        return $copy;
    }

    public function withValues(array $values): static
    {
        $copy = clone $this;

        foreach ($values as $name => $value) {
            if ($value !== null) {
                $copy->values[$name] = $value;
            } else {
                unset($copy->values[$name]);
            }
        }

        return $copy;
    }

    public function withoutValues(array $values): static
    {
        $copy = clone $this;

        foreach ($values as $name) {
            unset($copy->values[$name]);
        }

        return $copy;
    }

    public function withoutAllValues(): static
    {
        $copy = clone $this;
        $copy->values = [];
        return $copy;
    }

    public function withFields(array $fields): static
    {
        $copy = clone $this;
        $copy->fields = array_unique(array_merge($copy->fields, $fields));
        return $copy;
    }

    public function setValue(string $name, $value): void
    {
        if ($value !== null) {
            $this->values[$name] = $value;
        } else {
            unset($this->values[$name]);
        }
    }

    public function unsetValue(string $name): void
    {
        unset($this->values[$name]);
    }

    public function hasValue(string $name): bool
    {
        return isset($this->values[$name]);
    }

    public function getValue(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }

    public function getAllValues(): array
    {
        return $this->values;
    }

    public function getFieldsValues(): array
    {
        $values = $this->values;

        foreach ($this->fields as $name) {
            if (isset($values[$name])) {
                $result[$name] = $values[$name];
                unset($values[$name]);
            }
        }

        return $result ?? [];
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function offsetExists($offset): bool
    {
        return $this->hasValue($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->getValue($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->setValue($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->unsetValue($offset);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->values);
    }

    public function appendValue(string $name, $value): void
    {
        if ($value === null) {
            return;
        }

        if (! $this->hasValue($name)) {
            $this->setValue($name, [$value]);
            return;
        }

        $list = $this->getValue($name);
        if (is_array($list)) {
            $list[] = $value;
        } else {
            $list = [$list, $value];
        }
        $this->setValue($name, $list);
    }
}