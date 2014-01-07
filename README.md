Hautelook Templated URI Bundle
==============================

Symfony2 Bundle for the [https://github.com/hautelook/TemplatedUriRouter](https://github.com/hautelook/TemplatedUriRouter)
library.

[![Build Status](https://secure.travis-ci.org/hautelook/TemplatedUriBundle.png?branch=master)](https://travis-ci.org/hautelook/TemplatedUriBundle)

## Installation

Run the following command (assuming you have installed composer.phar or composer binary),
or add to your `composer.json` and run `composer install`:

```bash
$ composer require "hautelook/templated-uri-bundle ~1.0"
```

Now add the bundle to your Kernel:

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Hautelook\TemplatedUriBundle\HautelookTemplatedUriBundle(),
        // ...
    );
}
```

## Usage

The bundle exposes a router service (`hautelook.router.template`) that will generate RFC-6570 compliant URLs.
Here is a sample on how you could use it:

```php
$templateLink = $this->get('hautelook.router.template')->generate('hautelook_demo_route',
    array(
        'page'   => '{page}',
        'sort'   => array('{sort}'),
        'filter' => array('{filter}'),
    )
);
```

This will produce a link similar to:

```
/demo?{&page}{&sort%5B%5D*}{&filter%5B%5D*}
```

If your route has requirements on parameters, that your placeholder will violate, you will have to disable strict
requirement checking. Example:

```php
$templatedRouter = $kernel->getContainer()->get('hautelook.router.template');
$templatedRouter->setOption('strict_requirements', null);
$templateLink = $templatedRouter->generate('hautelook_demo_route',
    array(
        'page'   => '{page}',
        'sort'   => array('{sort}'),
        'filter' => array('{filter}'),
    )
);
```

[RFC-6570]: https://tools.ietf.org/html/rfc6570
