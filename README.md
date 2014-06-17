Bolt Thumbs
===========

#### New and improved Thumbnail handler for Bolt Images

Here's a quick run through of how to get this running on a dev version of Bolt. It should hopefully be fairly simple.

#####If you use composer to pull / update bolt then this method will work

First add this repo to your `composer.json` so that it loads from github rather than Packagist.

```
    ....
    "repositories": [
        { "type": "vcs","url":  "https://github.com/bolt/bolt-thumbs"}
    ],
    ....
```

Then add bolt/thumbs as a requirement for the project.

```
    "require": {
        .....
        "bolt/thumbs": "dev-master",
        .....
    }
```

running a `composer update` should now get you the package. Then all we need to do is mount it as a controller.

To do this, just make one modification to the main Bolt\Application file in side the `initMountpoints()` methods and add in this mount command:

```
    // Mount the 'thumbnail' provider on /thumbs.
    $this->mount('/thumbs', new \Bolt\Thumbs\ThumbnailProvider());
```

#####If you manually manage your install without Composer: 

Download this project and put it somewhere in your project. 

If you are not using composer all you need to di is make sure is that the bolt-thumbs package can autoload correctly.

Add the same line as above to your Bolt\Application `initMountpoints()` method. Then download this package and make sure that the classes can be autoloaded.


