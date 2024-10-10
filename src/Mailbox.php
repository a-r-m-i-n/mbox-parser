<?php

namespace Armin\MboxParser;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends ArrayCollection<int, MailMessage>
 */
final class Mailbox extends ArrayCollection
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $md5Hash;

    /**
     * @var \DateTime
     */
    private $date;

    public function __construct(string $filePath, array $elements = [])
    {
        parent::__construct($elements);

        $this->filePath = $filePath;
        $this->md5Hash = md5_file($filePath) ?: '';
        $this->date = new \DateTime();
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getMd5Hash(): string
    {
        return $this->md5Hash;
    }

    protected function createFrom(array $elements)
    {
        return new static($this->filePath, $elements);
    }
}
