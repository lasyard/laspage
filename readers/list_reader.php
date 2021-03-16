<?php
class ListReader extends Reader
{
    protected $_dom;

    public function load($file)
    {
        $this->_dom = new DOMDocument;
        $this->_dom->load($file);
    }

    protected static function _getDOMAttr($node, $attr)
    {
        $t = $node->attributes->getNamedItem($attr);
        return $t ? $t->value : NULL;
    }

    protected function _exts()
    {
        $xpath = new DOMXPath($this->_dom);
        $exts = $xpath->query("/list/exts/ext");
        $r = array();
        foreach ($exts as $ext) {
            $r[] = $ext->nodeValue;
        }
        return $r;
    }

    protected function _exclusive()
    {
        $xpath = new DOMXPath($this->_dom);
        $exclusive = $xpath->query("/list/files/@exclusive");
        if ($exclusive->length > 0 && $exclusive->item(0)->nodeValue == "yes") {
            return true;
        }
        return false;
    }

    protected function _files()
    {
        $xpath = new DOMXPath($this->_dom);
        $files = $xpath->query("/list/files/file");
        $r = array();
        foreach ($files as $file) {
            $name = $file->textContent;
            $title = self::_getDOMAttr($file, 'title');
            $info = self::_getDOMAttr($file, 'info');
            $style = self::_getDOMAttr($file, 'style');
            $priv = self::_getDOMAttr($file, 'priv');
            $dict = self::_getDOMAttr($file, 'dict');
            $r[$name] = compact('title', 'info', 'style', 'priv', 'dict');
        }
        return $r;
    }

    protected function _links()
    {
        $xpath = new DOMXPath($this->_dom);
        $links = $xpath->query("/list/links/link");
        $r = array();
        foreach ($links as $link) {
            $title = $link->textContent;
            $url = self::_getDOMAttr($link, 'url');
            $info = self::_getDOMAttr($link, 'info');
            $target = self::_getDOMAttr($link, 'target');
            $r[] = compact('title', 'url', 'info', 'target');
        }
        return $r;
    }
}
