<?php

namespace Armin\MboxParser;

/**
 * @implements \ArrayAccess<int, MailAddress>
 */
class MailAddressCollection implements \ArrayAccess, \Countable
{
    /**
     * @var array|MailAddress[]
     */
    private $addresses = [];

    public function __construct(array $addresses = [])
    {
        foreach ($addresses as $address) {
            $this->add($address);
        }
    }

    public function add(MailAddress $address): void
    {
        $this->addresses[] = $address;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->addresses[$offset]) || array_key_exists($offset, $this->addresses);
    }

    public function offsetGet($offset): ?MailAddress
    {
        return $this->addresses[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->addresses[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->addresses[$offset]);
    }

    public function count(): int
    {
        return count($this->addresses);
    }

    public function __toString()
    {
        $parts = [];
        foreach ($this->addresses as $address) {
            $parts[] = (string)$address;
        }

        return implode(', ', $parts);
    }
}
