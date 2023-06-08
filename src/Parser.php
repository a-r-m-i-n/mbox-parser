<?php

namespace Armin\MboxParser;

final class Parser
{
    public function parse(string $filePath): Mailbox
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
            while (!feof($handle)) {
                $line = fgets($handle);
                if (!$line) {
                    continue;
                }

                // RFC 4155 (check for "From "-line
                if (0 === strpos($line, 'From ')) {
                    if (!empty($lines)) {
                        $result->add(new MailMessage($lines));
                        $lines = [];
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
                            $result->add(new MailMessage($previousLines));
                            $nextLines = array_slice($lines, $sliceAt + 1);
                            $lines = $nextLines;
                        }

                        $boundary = $newBoundary;
                    }
                    $isMessage = false;
                }
            }
            if (!empty($lines)) {
                $result->add(new MailMessage($lines));
            }
            fclose($handle);
        }

        return $result;
    }
}
