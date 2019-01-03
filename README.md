Bolt Thumbs
===========

Image Thumbnail handler for Bolt
--------------------------------


### Installation

Bolt Thumbs is installed as a default dependency of Bolt. However, for use 
independently it can be included in your Composer project:

```bash
composer require bolt/thumbs:^3.4
```

Should you want to work with the development branch(es), you can specficy those
as Composer requirements instead.

To install the 3.x _development_ branch:

```bash
composer require bolt/thumbs:dev-3.x@dev
```

Alternatively to install the master branch:

```bash
composer require bolt/thumbs:dev-master@dev
```

### Configuration

If you've already got a working installation of Bolt, you can safely skip this.

To use the Botl Thumbs service provider onto your Silex based application, 
simply `->register()` the `\Bolt\Provider\ThumbnailsServiceProvider` class in 
your registration phase, e.g.:


```php
    /** @var \Silex\Application $app */
    $app->register(new \Bolt\Provider\ThumbnailsServiceProvider())
```
