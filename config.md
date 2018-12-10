# Configuration options

Default options are in config-default.php

Do not edit config-default.php unless you are making your own fork of project. All custom config options should be added to config.php. Create empty config.php:

```
<?php

$config = [];
```

then add custom configuration variables to it.


## Server configuration

#### region

Region string to identify server. Set it if you run multiple servers to easily identify which server you are conneting to.

To check which server you are connected to, open /version in browser.

#### env-region

If true, script will check for environment variable "REGION" and if it is set, it will overwrite configuration option "region"

#### custom-icons-dir

Directory with custom json files.

#### serve-default-icons

True if default Iconify icons set should be served.

#### index-page

URL to redirect browser when browsing main page. Redirection is permanent.

#### cache-dir

Directory for response cache.


## Browser cache controls

Cache configuration is stored in "cache" object. Object properties:

#### timeout

Cache timeout, in seconds.

#### min-refresh

Minimum page refresh timeout. Usually same as "timeout" value.

#### private

Set to true if page cache should be treated as private.


## Synchronizing icon sets with Git

Server can pull collections from Git service. This can be used to push collections to server whenever its updated without manual work.

There are two collections available: iconify and custom.

All configuration options are in "sync" object in config-default.json.

To synchronize repository send GET request to /sync?repo=iconify&key=your-sync-key
Replace repo with "custom" to synchronize custom repository and key with value of sync.secret

Server will respond identically with empty message regardless of status to prevent visitors from trying to guess your secret key. There is small delay in response.

Sync function is meant to be used with GitHub web hooks function.

#### secret

Secret key. String. This is required configuration option. Put it in config.json, not config-default.json to make sure its not commited by mistake.

If "secret" is not set, entire synchronization module is disabled.

#### versions

Location of versions.json file that stores information about latest synchronized repositories.

#### storage

Location of directory where repositories will be stored.

#### git

Git command. You can change it if you need to customize command that is executed to clone repository. {repo} will be replaced with repository URL, {target} will be replaced with target directory.

#### iconify

URL of Iconify icons repository.

#### custom

URL of custom icons repository.

#### custom-dir

Location of json files in custom repository, relative to root directory of repository.

For example, if json files are located in directory "json" in your repository (like they are in iconify repository), set custom-dir value to "json".
