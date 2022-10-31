<?php

namespace Armin\MboxParser;

class MailAddress
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $email
     * @param string $name
     */
    public function __construct($email, $name)
    {
        $this->email = $email;
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!empty($this->getName())) {
            return $this->getName() . ' <' . $this->getEmail() . '>';
        }

        return $this->getEmail();
    }
}
