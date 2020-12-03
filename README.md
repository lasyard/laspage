# README

## Usage

Put laspage in your project

```bash
cd "dir_of_your_project"
git clone "git@github.com:lasyard/laspage.git"
```

Create a directory to put the public resources

```bash
mkdir "pub"
```

Rewrite all to `index.php` except `/pub`. for example, in `.htaccess`

```apache
DirectoryIndex disabled
Options -Indexes -Multiviews
RewriteEngine On

RewriteRule !^pub index.php [L,NS]
```

Make a symlink from `laspage/pub` to `pub/sys`

```bash
ln -sf "../laspage/pub" "pub/sys"
```

Create a file `index.php`. It may looks like

```php
<?php
define('ROOT_PATH', __DIR__);
require_once 'sys/sys.php';
Sys::app()->run();
```
