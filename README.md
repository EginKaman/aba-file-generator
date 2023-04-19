# ABA File Generator for Laravel

## Overview
Generates an aba file for bulk banking transactions with Australian banks.

1. Install:
```bash
composer require eginkaman/aba-file-generator
```

2. Add to `config/app.php` to section `providers`:
```php
EginKaman\AbaFileGenerator\Providers\AbaFileGeneratorServiceProvider::class,
```

3. Add to `config/app.php` to section `aliases`:

```php
'AbaFileGenerator' => EginKaman\AbaFileGenerator\Facades\AbaFileGenerator::class,
```

4. Publish config file:
```bash
php artisan vendor:publish --provider="EginKaman\AbaFileGenerator\Providers\AbaFileGeneratorServiceProvider"
```

5. Use the facade where you need it, like:
```php
AbaFileGenerator::generate(array<\EginKaman\AbaFileGenerator\Contracts\Transaction::class>));
```

## Set required `.env` variable:
* ABA_BSB
* ABA_ACCOUNT_NUMBER
* ABA_BANK_NAME
* ABA_USER_NAME
* ABA_REMITTER
* ABA_DIRECT_ENTRY_ID

## References
- http://www.anz.com/Documents/AU/corporate/clientfileformats.pdf
- http://www.cemtexaba.com/aba-format/cemtex-aba-file-format-details.html
- https://github.com/mjec/aba/blob/master/sample-with-comments.aba
- https://www.fileactive.anz.com/filechecker

## TODO
* [ ] Make method for creating new instance AbaFileGenerator
* [ ] Check correct work on other PHP and Laravel versions
* [ ] Update docs
* [ ] Tests
* [ ] ...
