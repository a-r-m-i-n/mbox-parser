<?php

namespace Armin\MboxParser;

use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\MailMimeParser;

class MailMessage
{
    /**
     * @var IMessage
     */
    private $message;
    /**
     * @var array
     */
    private $messageLines;

    /**
     * @var MailMimeParser
     */
    private $messageParser;

    /**
     * @var bool
     */
    private $initialized = false;

    public function __construct(array $messageLines, MailMimeParser $messageParser = null)
    {
        $this->messageLines = $messageLines;
        $this->messageParser = $messageParser ?? new MailMimeParser();
    }

    private function init(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        $stream = fopen('php://memory', 'r+');
        if (!$stream) {
            throw new \RuntimeException('Unable to create file stream in php://memory');
        }

        fwrite($stream, implode('', $this->messageLines));
        rewind($stream);
        $this->message = $this->messageParser->parse($stream, false);
    }

    public function getMessage(): IMessage
    {
        $this->init();

        return $this->message;
    }

    public function getMessageId(): string
    {
        return $this->getMessage()->getHeaderValue(HeaderConsts::MESSAGE_ID) ?? '';
    }

    /**
     * @return array|MailAddress[]
     */
    public function getTo(): array
    {
        $addresses = [];

        $to = $this->getMessage()->getHeader(HeaderConsts::TO);
        if (!$to) {
            return $addresses;
        }

        /** @var AddressPart $part */
        foreach ($to->getParts() as $part) {
            $addresses[] = new MailAddress($part->getEmail(), $part->getName());
        }

        return $addresses;
    }

    public function getFrom(): string
    {
        return $this->getMessage()->getHeaderValue(HeaderConsts::FROM) ?? '';
    }

    public function getFromName(): string
    {
        $from = $this->getMessage()->getHeader(HeaderConsts::FROM);
        if (!$from) {
            return '';
        }
        /** @var AddressPart $addressPart */
        $addressPart = $from->getParts()[0];

        return $addressPart->getName();
    }

    /**
     * @return array|MailAddress[]
     */
    public function getReplyTo(): array
    {
        $addresses = [];
        $replyTo = $this->getMessage()->getHeader(HeaderConsts::REPLY_TO);
        if (!$replyTo) {
            return $addresses;
        }

        /** @var AddressPart $part */
        foreach ($replyTo->getParts() as $part) {
            $addresses[] = new MailAddress($part->getEmail(), $part->getName());
        }

        return $addresses;
    }

    public function getDate(): \DateTime
    {
        return new \DateTime($this->getMessage()->getHeaderValue(HeaderConsts::DATE) ?? 'now');
    }

    public function getSubject(): string
    {
        return $this->getMessage()->getHeaderValue(HeaderConsts::SUBJECT) ?? '';
    }

    public function getText(): string
    {
        return $this->getMessage()->getTextContent() ?? '';
    }

    public function getHtml(): string
    {
        return $this->getMessage()->getHtmlContent() ?? '';
    }

    /**
     * @return array|MailAttachment[]
     */
    public function getAttachments(): array
    {
        $attachmentParts = $this->getMessage()->getAllAttachmentParts();

        $attachments = [];
        foreach ($attachmentParts as $attachmentPart) {
            $attachments[] = new MailAttachment($attachmentPart->getFilename() ?? '', $attachmentPart->getContent(), $attachmentPart->getContentType());
        }

        return $attachments;
    }

    /**
     * @internal For debugging only
     */
    public function getMessageLines(): array
    {
        return $this->messageLines;
    }
}
