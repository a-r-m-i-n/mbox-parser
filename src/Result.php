<?php

namespace Armin\MboxParser;

class Result
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

    /**
     * @var array
     */
    private $messages = [];

    public function __construct(string $filePath)
    {
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

    /**
     * @internal Used by parser only
     */
    public function addMessage(MailMessage $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return array|MailMessage[]
     */
    public function getAllMessages(): array
    {
        return $this->messages;
    }
}
