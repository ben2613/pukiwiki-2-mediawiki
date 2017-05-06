Part of this is a php port from [Edvakf Gist](https://gist.github.com/edvakf/68624)

# Prerequisite:
Turn on your mediawiki `LocalSettings.php`

```
$wgEnableAPI = true;
$wgEnableWriteAPI = true;
```

# Usage:

1. Set your `$puki_dir` and `$mediawiki_dir` path at main.php

2. Run 'php main.php' at console


## Explanation
1. It scans the directory puki_wiki

2. Convert content in pukiwiki to mediawiki format

3. Then use exec (DIRTY?) edit.php at media_wiki to import it

# TODO

* Fix some heading align
* Fix link

