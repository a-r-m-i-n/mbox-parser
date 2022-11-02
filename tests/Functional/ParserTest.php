<?php

use Armin\MboxParser\MailAddress;
use Armin\MboxParser\MailMessage;
use Armin\MboxParser\Parser;
use Armin\MboxParser\Mailbox;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testParserWithInvalidFilePath(): void
    {
        $parser = new Parser();
        $this->expectException(InvalidArgumentException::class);
        $invalidFilePath = __DIR__ . '/non-existing-file.mbox';
        $this->expectExceptionMessage(sprintf('Unable to open mbox file "%s". File not found!', $invalidFilePath));
        $parser->parse($invalidFilePath);
    }

    public function testParserWithTypo3TestMails(): void
    {
        // This mbox file contains three mails, send by TYPO3's mail test tool (located in install tool)
        $parser = new Parser();
        $subject = $parser->parse($filePath = __DIR__ . '/../Fixtures/typo3-test-mail-setup.mbox');

        self::assertInstanceOf(Mailbox::class, $subject);
        self::assertIsIterable($subject);
        self::assertCount(3, $subject);

        self::assertSame($filePath, $subject->getFilePath());
        self::assertInstanceOf(DateTime::class, $subject->getDate());
        self::assertEquals((new DateTime())->setTime(0, 0), $subject->getDate()->setTime(0, 0));
        self::assertSame(md5_file($filePath), $subject->getMd5Hash());

        $values = [
            0 => ['to' => 'armin@v.ieweg.de', 'message-id' => '2026f546d879a98e610829b5dd9d43ba@example.com'],
            1 => ['to' => 'info@v.ieweg.de', 'message-id' => '2ed7f25ce1d10ac82d05b9c60a4a3235@example.com'],
            2 => ['to' => 'vieweg@iwkoeln.de', 'message-id' => '9be46546a050ee93011515b8e92a5901@example.com'],
        ];

        /** @var MailMessage $message */
        foreach ($subject as $index => $message) {
            self::assertSame($values[$index]['to'], $message->getTo()->get(0)->getEmail());

            self::assertSame('no-reply@example.com', $message->getFrom());
            self::assertSame('TYPO3 CMS install tool', $message->getFromName());
            self::assertSame('', (string) $message->getReplyTo());
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

        self::assertInstanceOf(Mailbox::class, $subject);
        self::assertIsIterable($subject);
        self::assertCount(3, $subject);

        // Second mail got attachments, first and third mail are identically
        /** @var MailMessage $secondMail */
        $secondMail = $subject->get(1);

        self::assertSame('recipient@domain.com', $secondMail->getTo()->get(0)->getEmail());
        self::assertSame('Robert Recipient', $secondMail->getTo()->get(0)->getName());
        self::assertSame(MailAddress::TYPE_TO, $secondMail->getTo()->get(0)->getType());
        self::assertSame('', (string) $secondMail->getCc());
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
        self::assertSame(592, $attachments[0]->getContentSize());
        self::assertSame('README.md', $attachments[1]->getFilename());
        self::assertSame('text/markdown', $attachments[1]->getContentMimeType());
        self::assertStringStartsWith('# EXT:mbox (Mail Client)', $attachments[1]->getContent());
        self::assertSame(2114, $secondMail->getSize());
        self::assertStringContainsString('Content-Type: multipart/mixed; boundary=8-dgLuxH', $secondMail->getMessageSource());

        // Testing first and third mail
        /** @var MailMessage $firstMail */
        $firstMail = $subject->get(0);
        self::assertSame('', $firstMail->getFromName());
        self::assertSame('sender@domain.com', $firstMail->getFrom());
        self::assertCount(2, $firstMail->getTo());
        self::assertSame('recipient1@domain.com', (string)$firstMail->getTo()->get(0));
        self::assertSame('recipient2@domain.com', (string)$firstMail->getTo()->get(1));
        self::assertSame('recipient1@domain.com, recipient2@domain.com', (string) $firstMail->getTo());
        self::assertSame('cc@domain.com', (string)$firstMail->getCc());
        self::assertSame('replyTo@domain.com', (string)$firstMail->getReplyTo()->get(0));
        self::assertEquals(new \DateTime('Mon, 31 Oct 2022 11:20:58 +0000'), $firstMail->getDate());
        self::assertSame('Test Mail #1', $firstMail->getSubject());
        self::assertSame('This is the text body of the mail', $firstMail->getText());
        self::assertSame('This is the <strong>HTML body</strong> of the mail', trim($firstMail->getHtml()));

        /** @var MailMessage $thirdMail */
        $thirdMail = $subject->get(2);
        self::assertSame('', $thirdMail->getFromName());
        self::assertSame('sender@domain.com', $thirdMail->getFrom());
        self::assertCount(3, $thirdMail->getTo());
        self::assertSame('Robert Recipient <recipient@domain.com>', (string)$thirdMail->getTo()[0]);
        self::assertSame('recipient1@domain.com', (string)$thirdMail->getTo()->get(1));
        self::assertSame('recipient2@domain.com', (string)$thirdMail->getTo()->get(2));
        self::assertSame('cc@domain.com', (string)$thirdMail->getCc());
        self::assertSame('replyTo@domain.com', (string)$thirdMail->getReplyTo()->get(0));
        self::assertEquals(new \DateTime('Mon, 31 Oct 2022 11:20:58 +0000'), $thirdMail->getDate());
        self::assertSame('Test Mail #3', $thirdMail->getSubject());
        self::assertSame('This is the text body of the mail', $thirdMail->getText());
        self::assertSame('This is the <strong>HTML body</strong> of the mail', trim($thirdMail->getHtml()));
    }

    public function testParserMailboxGetByMessageId(): void
    {
        $parser = new Parser();
        $subject = $parser->parse(__DIR__ . '/../Fixtures/typo3-test-mail-setup.mbox');

        /** @var MailMessage $mail1 */
        $mail1 = $subject->getMessageById($id = '2026f546d879a98e610829b5dd9d43ba@example.com');
        self::assertInstanceOf(MailMessage::class, $mail1);
        self::assertSame('armin@v.ieweg.de', $mail1->getTo()->get(0)->getEmail());
        self::assertSame($id, $mail1->getMessageId());

        /** @var MailMessage $mail2 */
        $mail2 = $subject->getMessageById($id = '2ed7f25ce1d10ac82d05b9c60a4a3235@example.com');
        self::assertInstanceOf(MailMessage::class, $mail2);
        self::assertSame('info@v.ieweg.de', $mail2->getTo()->get(0)->getEmail());
        self::assertSame($id, $mail2->getMessageId());

        $invalidMail = $subject->getMessageById('abc123@example.com');
        self::assertSame(null, $invalidMail);

        /** @var MailMessage $mail3 */
        $mail3 = $subject->getMessageById($id = '9be46546a050ee93011515b8e92a5901@example.com');
        self::assertInstanceOf(MailMessage::class, $mail3);
        self::assertSame('vieweg@iwkoeln.de', $mail3->getTo()->get(0)->getEmail());
        self::assertSame($id, $mail3->getMessageId());
    }
}
