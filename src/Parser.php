<?php

namespace Armin\MboxParser;

final class Parser
{
    private const MODE_REGULAR = 0;
    private const MODE_COUNT = 1;
    private const MODE_SINGLE = 2;

    /** @var int */
    private $mode = self::MODE_REGULAR;
    /** @var int */
    private $count = 0;
    /** @var string */
    private $messageId = '';

    public function parse(string $filePath, int $page = 1, int $perPage = 10): Mailbox
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(sprintf('Unable to open mbox file "%s". File not found!', $filePath));
        }

        $result = new Mailbox($filePath);

        $handle = fopen($filePath, 'r');
        if ($handle) {
            $lines = [];
            $isMessage = false;
            $boundary = null;
            $currentMessageIndex = 0;
            $start = ($page - 1) * $perPage;
            $end = $start + $perPage;

            while (!feof($handle)) {
                $line = fgets($handle);
                if (!$line) {
                    continue;
                }

                // RFC 4155 (check for "From "-line
                if (0 === strpos($line, 'From ')) {
                    if (!empty($lines)) {
                        if ($currentMessageIndex >= $start && $currentMessageIndex < $end) {
                            $message = new MailMessage($lines);
                            if (self::MODE_REGULAR === $this->mode) {
                                $result->add($message);
                            } elseif (self::MODE_SINGLE === $this->mode) {
                                if ($message->getMessageId() === $this->messageId) {
                                    $result->add($message);

                                    return $result;
                                }
                            }
                            ++$this->count;
                        }
                        $lines = [];
                        ++$currentMessageIndex;
                        if ($currentMessageIndex >= $end) {
                            break;
                        }
                    }
                    $isMessage = true;
                    continue;
                }

                // If no "From "-line found, try to identify mails by boundary (only works, if mails got boundary)
                $lines[] = $line;

                if (0 === strpos($line, 'Message-ID:')) {
                    $isMessage = true;
                }

                if ($isMessage && 0 === strpos($line, 'Content-Type:') && false !== strpos($line, 'boundary=')) {
                    // this is a new mail
                    $newBoundary = trim(preg_replace('/.*boundary=(.*?)/i', '$1', $line) ?? '');

                    if (null === $boundary) {
                        $boundary = $newBoundary;
                    } else {
                        // New Boundary found
                        // Find latest boundary in already collected lines and split
                        $sliceAt = null;
                        foreach ($lines as $index => $collectedLine) {
                            if (trim($collectedLine) === '--' . $boundary . '--') {
                                $sliceAt = $index;
                                break;
                            }
                        }

                        if (null !== $sliceAt) {
                            $previousLines = array_slice($lines, 0, $sliceAt);
                            if ($currentMessageIndex >= $start && $currentMessageIndex < $end) {
                                $message = new MailMessage($previousLines);
                                if (self::MODE_REGULAR === $this->mode) {
                                    $result->add($message);
                                } elseif (self::MODE_SINGLE === $this->mode) {
                                    if ($message->getMessageId() === $this->messageId) {
                                        $result->add($message);

                                        return $result;
                                    }
                                }
                                ++$this->count;
                            }
                            $nextLines = array_slice($lines, $sliceAt + 1);
                            $lines = $nextLines;
                            ++$currentMessageIndex;
                            if ($currentMessageIndex >= $end) {
                                break;
                            }
                        }

                        $boundary = $newBoundary;
                    }
                    $isMessage = false;
                }
            }
            if (!empty($lines) && $currentMessageIndex >= $start && $currentMessageIndex < $end) {
                $message = new MailMessage($lines);
                if (self::MODE_REGULAR === $this->mode) {
                    $result->add($message);
                } elseif (self::MODE_SINGLE === $this->mode) {
                    if ($message->getMessageId() === $this->messageId) {
                        $result->add($message);

                        return $result;
                    }
                }
                ++$this->count;
            }
            fclose($handle);
        }

        return $result;
    }

    public function getTotalEntries(string $filePath): int
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(sprintf('Unable to open mbox file "%s". File not found!', $filePath));
        }

        $this->mode = self::MODE_COUNT;
        $this->count = 0;
        $this->parse($filePath, 1, PHP_INT_MAX);
        $this->mode = self::MODE_REGULAR;

        return $this->count;
    }

    public function getMessageById(string $filePath, string $messageId): ?MailMessage
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(sprintf('Unable to open mbox file "%s". File not found!', $filePath));
        }

        $this->mode = self::MODE_SINGLE;
        $this->messageId = $messageId;

        $mailbox = $this->parse($filePath, 1, PHP_INT_MAX);

        $this->mode = self::MODE_REGULAR;
        $this->messageId = '';

        return $mailbox->first() ?: null;
    }
}
