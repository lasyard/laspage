<?php
class Node extends Base
{
    protected $_path;
    protected $_isDir;
    protected $_resolved;
    protected $_name;
    protected $_title;
    protected $_baseUrl;
    protected $_exts = array('html', 'php');
    protected $_files;
    protected $_exclusive;
    protected $_links;
    protected $_realFiles;
    protected $_reader;
    protected $_annex;

    public function __construct()
    {
        $this->_baseUrl = Sys::app()->base;
        $this->_path = STREAM_PROTOCOL . '://';
        $this->_initDir();
    }

    public function rollTo($name)
    {
        if (empty($name)) return;
        if (!$this->_hasPrivTo($name)) Sys::app()->error("Not allowed!");
        $this->_name = $name;
        $newPath = $this->_path . '/' . $name;
        if (isset($this->_files[$name])) {
            $newTitle = $this->_files[$name]['title'];
        }
        if (empty($newTitle)) $newTitle = $this->_getTitle($name);
        $this->_title = $newTitle;
        if (is_dir($newPath)) {
            $this->_baseUrl .= $name . '/';
            Sys::app()->addBreadcrumb(array(
                'title' => $this->_title,
                'url' => $this->_baseUrl,
            ));
            $this->_path = $newPath;
            $this->_initDir();
        } else {
            $this->_isDir = false;
            $this->_createFileList();
            if (array_key_exists($name, $this->_realFiles)) {
                $this->_resolved = true;
            } else {
                $this->_resolved = false;
            }
        }
    }

    public function cook($args)
    {
        $app = Sys::app();
        if ($this->_isDir) {
            $this->_createFileList();
            $realPath = $this->_searchIndexFile();
        } else {
            $realPath = $this->_realFiles[$this->_name]['realPath'];
        }
        $this->_genFileLinks();
        if (isset($realPath)) {
            $this->_genRelatedLinks($realPath);
            $ext = pathinfo($realPath, PATHINFO_EXTENSION);
            $reader = $app->getReader($ext);
            if ($reader) $reader->load($realPath);
            if ($ext == 'php') $reader->cookArgs($this->_baseUrl, $args);
        }
        if (!isset($reader)) {
            $reader = new VirtualReader;
            $reader->load("File does not exists!");
        }
        $title = $reader->title;
        if (!empty($title)) $this->_title = $title;
        $app->title = $this->_title;
        $fileInfo = NULL;
        if (isset($this->_files[$this->_name])) {
            $fileInfo = $this->_files[$this->_name]['info'];
        }
        $app->info = Sys::renderHtml('info', array(
            'date' => $this->_getDate(),
            'fileInfo' => $fileInfo,
        ));
        if ($reader->styles) {
            foreach ($reader->styles as $style) $app->addStyle($style);
        }
        if (isset($this->_files[$this->_name]['style'])) {
            $fileStyle = $this->_files[$this->_name]['style'];
            $app->addStyle($fileStyle);
        }
        if ($reader->scripts) {
            foreach ($reader->scripts as $script) $app->addScript($script);
        }
        if ($reader->cssText) {
            $app->addCssText($reader->cssText);
        }
        $this->_reader = $reader;
        if (isset($realPath)) {
            $annexFile = $this->_annexFile($realPath);
            if (is_file($annexFile)) {
                $annex = new AnnexReader;
                $annex->load($annexFile);
                if ($annex->styles) {
                    foreach ($annex->styles as $style) $app->addStyle($style);
                }
                $this->_annex = $annex;
            }
        }
    }

    protected function isRoot()
    {
        return rtrim($this->_path, '/:') == STREAM_PROTOCOL;
    }

    protected function httpHeaders()
    {
        return isset($this->_reader) ? $this->_reader->httpHeaders : NULL;
    }

    protected function content()
    {
        $content = $this->_reader->body;
        if (isset($this->_annex)) $content .= $this->_annex->body;
        return $content;
    }

    protected function _initDir()
    {
        $this->_isDir = true;
        $this->_resolved = true;
        $this->_realFiles = NULL;
        $this->_loadListFile();
        $this->_name = '';
    }

    protected function _searchIndexFile()
    {
        $indexFile = null;
        $path = $this->_path;
        $d = @opendir($path);
        if (!$d) return $indexFile;
        while (($file = readdir($d)) !== false) {
            if (pathinfo($file, PATHINFO_FILENAME) != 'index') continue;
            if (is_file("$path/$file")) {
                $indexFile = "$path/$file";
                break;
            }
        }
        closedir($d);
        return $indexFile;
    }

    protected function _getTitle($name)
    {
        if ($name == 'index') return '';
        $words = explode('_', $name);
        if (preg_match('/^\d{6}$/', $words[0])) array_shift($words);
        foreach ($words as &$word) $word = ucfirst($word);
        $title = implode(' ', $words);
        return $title;
    }

    protected function _getDate()
    {
        $matches = array();
        if (preg_match('/^(\d{6})_/', $this->_name, $matches)) {
            return '20' . implode('.', str_split($matches[1], 2));
        }
        return NULL;
    }

    protected function _loadListFile()
    {
        $this->_files = array();
        $this->_exclusive = false;
        $this->_links = array();
        $listFile = $this->_path . '/list.xml';
        if (!is_file($listFile)) return;
        $list = new ListReader;
        $list->load($listFile);
        $this->_exts = array_unique(array_merge($this->_exts, $list->exts));
        $this->_exclusive = $list->exclusive;
        $this->_files = $list->files;
        $this->_links = $list->links;
    }

    protected function _createFileList()
    {
        $this->_realFiles = $this->_fileListOfPath($this->_path, array(
            '/^index\./', '/^list\./', '/links\.xml$/', '/annex\.txt$/', '/_files$/',
        ));
    }

    protected function _fileListOfPath($path, $excludes)
    {
        $fileList = array();
        $d = @opendir($path);
        if (!$d) return $fileList;
        $isHidden = function ($f) use ($excludes) {
            if (substr($f, 0, 1) == '.') return true;
            foreach ($excludes as $pattern) {
                if (preg_match($pattern, $f)) return true;
            }
            return false;
        };
        while (($file = readdir($d)) !== false) {
            if ($isHidden($file)) continue;
            if (is_dir("$path/$file")) {
                $name = $file;
                $isDir = true;
            } else {
                $t = pathinfo($file);
                if (!isset($t['extension']) || !in_array($t['extension'], $this->_exts)) continue;
                $name = $t['filename'];
                $isDir = false;
            }
            $realPath = "$path/$file";
            $fileList[$name] = compact('isDir', 'realPath');
        }
        closedir($d);
        return $fileList;
    }

    protected function _hasPrivTo($name)
    {
        if (!empty($this->_files[$name]['priv'])) {
            $priv = $this->_files[$name]['priv'];
            return Sys::app()->hasPriv($priv);
        }
        return true;
    }

    protected function _newLink($name, $title = '', $info = '')
    {
        return array(
            'title' => empty($title) ? $this->_getTitle($name) : $title,
            'url' => $this->baseUrl . $name,
            'selected' => ($this->_name === $name),
            'isDir' => false,
            'info' => $info,
        );
    }

    protected function _genFileLinks()
    {
        $links = array();
        $restricted = array();
        foreach ($this->_files as $name => $file) {
            if (!$this->_hasPrivTo($name)) {
                $restricted[$name] = true;
                continue;
            }
            $links[$name] = $this->_newLink($name, $file['title'], $file['info']);
        }
        $extraLinks = array();
        foreach ($this->_realFiles as $name => $file) {
            if (array_key_exists($name, $restricted)) {
                continue;
            }
            if (array_key_exists($name, $links)) {
                $links[$name]['isDir'] = $file['isDir'];
            } else if (!$this->_exclusive) {
                $extraLinks[$name] = $this->_newLink($name);
                $extraLinks[$name]['isDir'] = $file['isDir'];
            }
        }
        uasort($extraLinks, function ($a, $b) {
            if ($a['isDir'] && !$b['isDir']) return -1;
            if (!$a['isDir'] && $b['isDir']) return 1;
            return strcasecmp($a['url'], $b['url']);
        });
        foreach ($links as $name => &$link) {
            if ($link['isDir']) $link['url'] .= '/';
            Sys::app()->addFileLink($link);
        }
        foreach ($extraLinks as $name => &$link) {
            if ($link['isDir']) $link['url'] .= '/';
            Sys::app()->addFileLink($link);
        }
    }

    protected function _genRelatedLinks($realPath)
    {
        foreach ($this->_links as $link) Sys::app()->addRelatedLink($link);
        $linksName = $this->_linkFile($realPath);
        if (is_file($linksName)) {
            $links = new ListReader;
            $links->load($linksName);
            foreach ($links->links as $link) {
                Sys::app()->addRelatedLink($link);
            }
        }
    }

    protected function _linkFile($path)
    {
        return substr_replace($path, '.links.xml', strrpos($path, '.'));
    }

    protected function _annexFile($path)
    {
        return substr_replace($path, '.annex.txt', strrpos($path, '.'));
    }
}
