# Configuration options

Default options are in config-default.json

Do not edit config-default.json unless you are making your own fork of project. All custom config options should be added to config.json. Create empty config.json:

```
{
}
```

then add custom configuration variables to it.


## Server configiration

#### region

Region string to identify server. Set it if you run multiple servers to easily identify which server you are conneting to.

To check which server you are connected to, open /version in browser.

#### env-region

If true, script will check for environment variable "REGION" and if it is set, it will overwrite configuration option "region"

#### custom-icon-dirs

List of directories with custom json files. By default list contains only directory "json".

Use {dir} variable to specify application's directory.

#### serve-default-icons

True if default SimpleSVG icons set should be served.

#### index-page

URL to redirect browser when browsing main page. Redirection is permanent.

#### cache-dir

Directory for response cache.

Use {dir} variable to specify application's directory.


## Browser cache controls

Cache configiration is stored in "cache" object. Object properties:

#### timeout

Cache timeout, in seconds.

#### min-refresh

Minimum page refresh timeout. Usually same as "timeout" value.

#### private

Set to true if page cache should be treated as private.
