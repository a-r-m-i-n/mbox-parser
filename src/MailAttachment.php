<?php

namespace Armin\MboxParser;

class MailAttachment
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var mixed
     */
    private $content;

    /**
     * @var string
     */
    private $contentMimeType;

    /**
     * @param string $filename
     * @param mixed  $content
     * @param string $contentMimeType
     */
    public function __construct($filename, $content, $contentMimeType)
    {
        $this->filename = $filename;
        $this->content = $content;
        $this->contentMimeType = $contentMimeType;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getContentSize()
    {
        return strlen($this->getContent());
    }

    public function getContentMimeType(): string
    {
        return $this->contentMimeType;
    }
}
