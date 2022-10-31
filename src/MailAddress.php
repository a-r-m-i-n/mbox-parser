<?php

namespace Armin\MboxParser;

class MailAddress
{
    public const TYPE_TO = 'to';
    public const TYPE_CC = 'cc';
    public const TYPE_BCC = 'bcc';

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $type See public class constants
     */
    public function __construct(string $email, string $name, string $type = self::TYPE_TO)
    {
        $this->email = $email;
        $this->name = $name;
        $this->type = $type;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function __toString(): string
    {
        if (!empty($this->getName())) {
            return $this->getName() . ' <' . $this->getEmail() . '>';
        }

        return $this->getEmail();
    }
}
