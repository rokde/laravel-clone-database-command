# This is my package laravel-clone-database-command

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rokde/laravel-clone-database-command.svg?style=flat-square)](https://packagist.org/packages/rokde/laravel-clone-database-command)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/rokde/laravel-clone-database-command/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/rokde/laravel-clone-database-command/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/rokde/laravel-clone-database-command/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/rokde/laravel-clone-database-command/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/rokde/laravel-clone-database-command.svg?style=flat-square)](https://packagist.org/packages/rokde/laravel-clone-database-command)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require rokde/laravel-clone-database-command
```

## Usage

You can use the pre-configured artisan console command:

```bash
php artisan db:clone
```

This assumes that there are the entries `source` and `target` in the database configuration.

Otherwise you can create your own clone command to meet the configurable needs.

All single tasks can be found in the `src/Actions` folder. So you can join it like you want if necessary. 

### Configuration

The whole configuration is stored in a class `DatabaseSyncConfiguration`.

#### source & target connection

The name of the connections and the connection configuration is editable. So if you already have a `target` or `source` connection configured - you can change that name if necessary.

#### chunk size

The chunk size can be configured for a specific table or for any table.

#### limit

The limit of rows can be configured for a specific table or for any table.

#### mutations

A mutation can be configured for a specific table or for any table. So the given column name can be used for any table when existent. So you can replace all `email` columns by a fake email like so:

```php
$config->addMutation('email', fn() => fake()->email);
```

#### behaviour

We can decided what to do with the tables already existing on the target: keep it as is, or drop all unhandled tables.

Another option is to delete records before inserting the new ones or should the table be dropped before and the structure should be stored newly.


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Robert Kummer](https://github.com/rokde)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
