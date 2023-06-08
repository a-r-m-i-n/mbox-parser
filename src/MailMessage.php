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

        /** @var resource $stream */
        $stream = fopen('php://memory', 'r+');
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

    public function getTo(): MailAddressCollection
    {
        $addresses = new MailAddressCollection();

        $to = $this->getMessage()->getHeader(HeaderConsts::TO);
        if (!$to) {
            return $addresses;
        }

        /** @var AddressPart $part */
        foreach ($to->getParts() as $part) {
            $addresses->add(new MailAddress($part->getEmail(), $part->getName()));
        }

        return $addresses;
    }

    public function getCc(): MailAddressCollection
    {
        $addresses = new MailAddressCollection();

        $cc = $this->getMessage()->getHeader(HeaderConsts::CC);
        if (!$cc) {
            return $addresses;
        }

        /** @var AddressPart $part */
        foreach ($cc->getParts() as $part) {
            $addresses->add(new MailAddress($part->getEmail(), $part->getName(), MailAddress::TYPE_CC));
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

    public function getReplyTo(): MailAddressCollection
    {
        $addresses = new MailAddressCollection();
        $replyTo = $this->getMessage()->getHeader(HeaderConsts::REPLY_TO);
        if (!$replyTo) {
            return $addresses;
        }

        /** @var AddressPart $part */
        foreach ($replyTo->getParts() as $part) {
            $addresses->add(new MailAddress($part->getEmail(), $part->getName()));
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
            $attachments[] = new MailAttachment(
                $attachmentPart->getFilename() ?? '',
                $attachmentPart->getContent(),
                $attachmentPart->getContentType() ?: 'text/plain',
                $attachmentPart->getContentId()
            );
        }

        return $attachments;
    }

    public function getSize(): int
    {
        return strlen(implode('', $this->messageLines));
    }

    public function getMessageSource(): string
    {
        return implode('', $this->messageLines);
    }
}
