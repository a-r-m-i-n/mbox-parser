# armin/mbox-parser (mbox Parser for PHP)

PHP library to parse mbox files (RFC 4155) to email messages. 

The mail messages (RFC 2822) are being parsed using the great library [zbateson/mail-mime-parser](https://github.com/zbateson/mail-mime-parser).

**armin/mbox-parser** is released under [MIT license](https://github.com/a-r-m-i-n/editorconfig-cli/blob/master/LICENSE).

Written by [Armin Vieweg](https://v.ieweg.de). Supported by IW Medien GmbH


## Requirements

- PHP 7.3 or higher


## Usage

### Iterate through mbox file

```php
<?php

$page = 1;
$itemsPerPage = 10;

$parser = new \Armin\MboxParser\Parser();
$mailbox = $parser->parse('path/to/file.mbox', $page, $itemsPerPage);

// $mailbox only contains the items from given page 
foreach ($mailbox as $mailMessage) {
    // ...
}
```

### Get amount of total items in mbox file

```php
<?php

$parser = new \Armin\MboxParser\Parser();
$total = $parser->getTotalEntries('path/to/file.mbox');
```

### Get specific mail message

```php
<?php

$parser = new \Armin\MboxParser\Parser();
$mailMessage = $parser->getMessageById('path/to/file.mbox', '2026f546d879a98e610829b5dd9d43ba@example.com')
```

### Working with mail message

See [tests](tests/Functional/ParserTest.php) for more examples of how to work with MailMessage.
