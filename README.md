# MBOX Parser for PHP

PHP library to parse mbox files to email messages. The mail messages are being parsed, using
the great library [zbateson/mail-mime-parser](https://github.com/zbateson/mail-mime-parser).


## Usage

```php
<?php

$parser = new \Armin\MboxParser\Parser();
$result = $parser->parse('path/to/file.mbox');

$arrayOfMessages = $result->getAllMessages();
```

See [tests](tests/Functional/ParserTest.php) for more examples.
