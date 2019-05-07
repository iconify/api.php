# Iconify API

This code runs on api.iconify.design that is used to serve collections and SVG images.

Make sure your server matches requirements below:

* You must have PHP 5.6 or newer version.
* Server must be running Apache with mod_rewrite enabled and AllowOverride enabled.
* Files must be writable by same user that runs PHP scripts to allow writing to "cache" and "git-repos" directories.
* If you are using function to synchronize repositories, make sure Git is installed and is accessable from command line.

Node.js version is available at https://github.com/iconify/api.js


### How to use it

Upload files in root directory of your website. Icons server requires its own domain or sub-domain.

Add custom configuration to config.php. Default configuration is available as reference in config-default.php. See [config.md](config.md)


### Node vs PHP

Node.js version of server is faster because it loads everything only once on startup. It is a bit harder to setup though because you need to install additional software and make sure server is running (using tools such as "pm2").

PHP process ends when HTTP request ends, so PHP has to reload lots of things for each request. PHP version has caching to minimize loading times, but it is still nowhere near as fast as Node.js version. The only upside of PHP version is it is easy to setup - simply upload files and you are done.

Node.js version has one feature that PHP version does not have: ability to send errors by email.

Use Node.js version if you can for better performance and better error reporting.
