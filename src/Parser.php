<?php

namespace Armin\MboxParser;

final class Parser
{
    public function parse(string $filePath): Result
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(sprintf('Unable to open mbox file "%s". File not found!', $filePath));
        }

        $result = new Result($filePath);

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

                        $previousLines = array_slice($lines, 0, $sliceAt);
                        $result->addMessage(new MailMessage($previousLines));
                        $nextLines = array_slice($lines, $sliceAt + 1);
                        $boundary = $newBoundary;
                        $lines = $nextLines;
                    }
                    $isMessage = false;
                }
            }
            $result->addMessage(new MailMessage($lines));
            fclose($handle);
        }

        return $result;
    }
}
