sampa
=====

lightweight, fast and easy to use php5 mvc framework

[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/appdeck/sampa/badges/quality-score.png?s=c9f09969cf01fb4f17994753a8fb191a870f8248)](https://scrutinizer-ci.com/g/appdeck/sampa/)
[![Build Status](https://travis-ci.org/appdeck/sampa.png?branch=master)](https://travis-ci.org/appdeck/sampa)
[![Latest Stable Version](https://poser.pugx.org/appdeck/sampa/v/stable.png)](https://packagist.org/packages/appdeck/sampa)
[![Total Downloads](https://poser.pugx.org/appdeck/sampa/downloads.png)](https://packagist.org/packages/appdeck/sampa)

Installation
------------
This library can be found on [Packagist](https://packagist.org/packages/appdeck/sampa).
The recommended way to install this is through [composer](http://getcomposer.org).

Edit your `composer.json` and add:

```json
{
    "require": {
        "appdeck/sampa": "dev-master"
    }
}
```

And install dependencies:

```bash
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar install
```

Features
--------
 - PSR-0 compliant for easy interoperability

Examples
--------
Examples of basic usage are located in the examples/ directory.

Bugs and feature requests
-------------------------
Have a bug or a feature request? [Please open a new issue](https://github.com/appdeck/sampa/issues).
Before opening any issue, please search for existing issues and read the [Issue Guidelines](https://github.com/necolas/issue-guidelines), written by [Nicolas Gallagher](https://github.com/necolas/).

Versioning
----------
sampa will be maintained under the Semantic Versioning guidelines as much as possible.

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

* Breaking backward compatibility bumps the major (and resets the minor and patch)
* New additions without breaking backward compatibility bumps the minor (and resets the patch)
* Bug fixes and misc changes bumps the patch

For more information on SemVer, please visit [http://semver.org/](http://semver.org/).

Authors
-------
**Flávio Heleno**

+ [http://twitter.com/flavioheleno](http://twitter.com/flavioheleno)
+ [http://github.com/flavioheleno](http://github.com/flavioheleno)

**Vinícius Campitelli**

+ [http://twitter.com/vcampitelli](http://twitter.com/vcampitelli)
+ [http://github.com/vcampitelli](http://github.com/vcampitelli)

Copyright and license
---------------------

Copyright 2016 appdeck under [MIT](LICENSE.md).
