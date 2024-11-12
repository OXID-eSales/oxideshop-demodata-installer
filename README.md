# OXID eShop DemoData installer

This component provides a way for OXID eShop setup functionality to copy demodata images.

## Installation
  
This component is installable via composer:

```
composer require --dev oxid-esales/oxideshop-demodata-installer
```

## Development

### Running tests

Component tests can be executed with the OXID eShop's PHPUnit runner:
```bash
vendor/bin/phpunit vendor/oxid-esales/oxideshop-demodata-installer
```

you might need to extend the eShop's root composer `autoload-dev` configuration and run `dump-autoload` command:

```json filename="composer.json"
    "autoload-dev": {
        "psr-4": {
            "OxidEsales\\DemoDataInstaller\\Tests\\": "./vendor/oxid-esales/oxideshop-demodata-installer/tests"
        }
    }
```

```bash
composer dump-autoload
```
to activate autoloading for the component's test classes.

Bugs and Issues
---------------

If you experience any bugs or issues, please report them in the section **OXID eShop (all versions)** of https://bugs.oxid-esales.com.
