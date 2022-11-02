# armin/mbox-parser (mbox Parser for PHP)

PHP library to parse mbox files to email messages. The mail messages are being parsed using
the great library [zbateson/mail-mime-parser](https://github.com/zbateson/mail-mime-parser).

**armin/mbox-parser** is released under [MIT license](https://github.com/a-r-m-i-n/editorconfig-cli/blob/master/LICENSE).

Written by [Armin Vieweg](https://v.ieweg.de). Supported by IW Medien GmbH


## Requirements

- PHP 7.3 or higher


## Usage

```php
<?php

$parser = new \Armin\MboxParser\Parser();
$mailbox = $parser->parse('path/to/file.mbox');

foreach ($mailbox as $mailMessage) {
    // ...
}
```

See [tests](tests/Functional/ParserTest.php) for more examples of how to work with MailMessage.
