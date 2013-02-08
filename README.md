# Eix

Eix is a PHP framework that helps building web applications quickly and easily.

The framework's operation is based on a simple response-request architecture, which could loosely be compared to a message passing pattern.

Its name means 'axis' in Catalan, and its roughly pronounced as 'ehsh'.


## Requirements

Eix requires PHP 5.3 or later, with the following modules enabled:
* libxml
* dom
* hash
* gettext
* SPL
* iconv
* json
* session
* mbstring
* standard
* Reflection
* phar
* sockets
* tokenizer
* xml
* curl
* imagick
* xsl


## License

This software is licensed under the MIT license, as stated in LICENSE.md.


## Deployment

The library is available as a [PHP archive (phar)](http://php.net/phar), and as a Composer library.


### PHP Archive (PHAR)

	wget http://eix.nohex.com/get/Eix.phar
	chmod +x Eix.phar

The phar can be deployed under `Nohex/Eix.phar` in your `include_path`.


### Composer

To add Eix as a local, per-project dependency to your project, simply add a dependency on `nohex/eix` to your project's `composer.json` file. Here is a minimal example of a `composer.json` file that just defines a development-time dependency on Eix 1.0:

	{
		"require-dev": {
			"nohex/eix": "1.0.*"
		}
	}


## Documentation

The documentation on Eix is available at http://eix.nohex.com/. This is also a sample web site that shows Eix's capabilities, and is also [a project on GitHub](http://github.com/noihex/eix-www).