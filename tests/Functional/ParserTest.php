<?php

use Armin\MboxParser\MailMessage;
use Armin\MboxParser\Parser;
use Armin\MboxParser\Result;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testParserWithTypo3TestMails(): void
    {
        // This mbox file contains three mails, send by TYPO3's mail test tool (located in install tool)
        $parser = new Parser();
        $subject = $parser->parse(__DIR__ . '/../Fixtures/typo3-test-mail-setup.mbox');

        self::assertInstanceOf(Result::class, $subject);
        self::assertIsArray($subject->getAllMessages());
        self::assertCount(3, $subject->getAllMessages());

        $values = [
            0 => ['to' => 'armin@v.ieweg.de', 'message-id' => '2026f546d879a98e610829b5dd9d43ba@example.com'],
            1 => ['to' => 'info@v.ieweg.de', 'message-id' => '2ed7f25ce1d10ac82d05b9c60a4a3235@example.com'],
            2 => ['to' => 'vieweg@iwkoeln.de', 'message-id' => '9be46546a050ee93011515b8e92a5901@example.com'],
        ];

        /** @var MailMessage $message */
        foreach ($subject->getAllMessages() as $index => $message) {
            self::assertSame($values[$index]['to'], $message->getTo()[0]->getEmail());

            self::assertSame('no-reply@example.com', $message->getFrom());
            self::assertSame('TYPO3 CMS install tool', $message->getFromName());
            self::assertSame('Test TYPO3 CMS mail delivery from site "EXT:mbox Dev Environment"', $message->getSubject());
            self::assertSame($values[$index]['message-id'], $message->getMessageId());
            self::assertStringStartsWith('TYPO3 Test Mail', $message->getText());
            self::assertStringStartsWith('<!doctype html>', $message->getHtml());
            self::assertStringContainsString('<h4>Hey TYPO3 Administrator</h4>', $message->getHtml());
            self::assertStringContainsString('<p>Seems like your favorite TYPO3 installation can send out emails!</p>', $message->getHtml());
        }
    }


    public function testParserWithSymfonyMails(): void
    {
        // This mbox file contains three mails send "CreateAndSendTestMailsCommand" in EXT:mbox
        // The second mail got file attachments(!)
        $parser = new Parser();
        $subject = $parser->parse(__DIR__ . '/../Fixtures/test-mails.mbox');

        self::assertInstanceOf(Result::class, $subject);
        self::assertIsArray($subject->getAllMessages());
        self::assertCount(3, $subject->getAllMessages());

        // Second mail got attachments, first and third mail are identically
        /** @var MailMessage $secondMail */
        $secondMail = $subject->getAllMessages()[1];

        self::assertSame('recipient@domain.com', $secondMail->getTo()[0]->getEmail());
        self::assertSame('Robert Recipient', $secondMail->getTo()[0]->getName());
        self::assertSame('Test Mail #2', $secondMail->getSubject());

        self::assertSame(2, $secondMail->getMessage()->getAttachmentCount());

        $attachments = $secondMail->getAttachments();
        self::assertCount(2, $attachments);
        self::assertSame('Extension.svg', $attachments[0]->getFilename());
        self::assertSame('text/svg', $attachments[0]->getContentMimeType());
        self::assertSame(<<<SVG
<svg viewBox="0 0 32 32" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="a"><stop offset="0" stop-color="#4F5B81"/><stop offset="1" stop-color="#3A4861"/></linearGradient><linearGradient xlink:href="#a" id="b" x1="6.943" y1="10.646" x2="7.072" y2="31.057" gradientUnits="userSpaceOnUse"/></defs><path d="M0 0h32v32H0z" fill="url(#b)"/><g fill-rule="evenodd"><path fill="#fff" fill-opacity=".5" d="M10.33 0h12v32h-12z"/><path fill="#B1DEF7" d="M10.33 20h12v12h-12z"/><path d="m16.339 27.433-2.664-2.78h5.328z" fill="#17416E"/></g></svg>

SVG
, $attachments[0]->getContent());
        self::assertSame('README.md', $attachments[1]->getFilename());
        self::assertSame('text/markdown', $attachments[1]->getContentMimeType());
        self::assertStringStartsWith('# EXT:mbox (Mail Client)', $attachments[1]->getContent());

        // Testing first and third mail
        $firstMail = $subject->getAllMessages()[0];
        self::assertCount(2, $firstMail->getTo());
        self::assertSame('recipient1@domain.com', (string)$firstMail->getTo()[0]);
        self::assertSame('recipient2@domain.com', (string)$firstMail->getTo()[1]);
        self::assertSame('replyTo@domain.com', (string)$firstMail->getReplyTo()[0]);
        self::assertEquals(new \DateTime('Mon, 31 Oct 2022 11:20:58 +0000'), $firstMail->getDate());
        self::assertSame('Test Mail #1', $firstMail->getSubject());
        self::assertSame('This is the text body of the mail', $firstMail->getText());
        self::assertSame('This is the <strong>HTML body</strong> of the mail', trim($firstMail->getHtml()));

        $thirdMail = $subject->getAllMessages()[2];
        self::assertCount(3, $thirdMail->getTo());
        self::assertSame('Robert Recipient <recipient@domain.com>', (string)$thirdMail->getTo()[0]);
        self::assertSame('recipient1@domain.com', (string)$thirdMail->getTo()[1]);
        self::assertSame('recipient2@domain.com', (string)$thirdMail->getTo()[2]);
        self::assertSame('replyTo@domain.com', (string)$thirdMail->getReplyTo()[0]);
        self::assertEquals(new \DateTime('Mon, 31 Oct 2022 11:20:58 +0000'), $thirdMail->getDate());
        self::assertSame('Test Mail #3', $thirdMail->getSubject());
        self::assertSame('This is the text body of the mail', $thirdMail->getText());
        self::assertSame('This is the <strong>HTML body</strong> of the mail', trim($thirdMail->getHtml()));
    }
}
