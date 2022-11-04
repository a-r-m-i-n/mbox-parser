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
     * @var string|null
     */
    private $contentId;

    /**
     * @param string      $filename
     * @param mixed       $content
     * @param string      $contentMimeType
     * @param string|null $contentId
     */
    public function __construct($filename, $content, $contentMimeType, $contentId)
    {
        $this->filename = $filename;
        $this->content = $content;
        $this->contentMimeType = $contentMimeType;
        $this->contentId = $contentId;
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

    public function getContentId(): ?string
    {
        return $this->contentId;
    }
}
