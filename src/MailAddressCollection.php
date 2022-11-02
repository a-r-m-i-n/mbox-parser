<?php

namespace Armin\MboxParser;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends ArrayCollection<int, MailAddress>
 */
class MailAddressCollection extends ArrayCollection
{
    public function __toString(): string
    {
        $parts = [];
        foreach ($this->getValues() as $address) {
            $parts[] = (string)$address;
        }

        return implode(', ', $parts);
    }
}
