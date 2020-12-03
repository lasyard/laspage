<?php
class HtmlReader extends Reader
{
    protected $_base;
    protected $_dom;

    public function load($file)
    {
        $this->_base = substr($file, 0, strrpos($file, '/'));
        $this->_dom = new DOMDocument;
        $this->_dom->loadHTMLFile($file);
    }

    public function loadStr($base, $str)
    {
        $this->_base = $base;
        $this->_dom = new DOMDocument;
        $this->_dom->loadHTML($str);
    }

    public function loadStrEnc($base, $str, $enc)
    {
        $this->_base = $base;
        $this->_dom = new DOMDocument;
        $this->_dom->loadHTML('<?xml encoding="' . $enc . '"?>' . $str);
    }

    protected function _title()
    {
        $xpath = new DOMXPath($this->_dom);
        $nodes = $xpath->query('/html/head/title');
        if ($nodes->length == 0) return '';
        return $nodes->item(0)->textContent;
    }

    protected function _cssText()
    {
        $xpath = new DOMXPath($this->_dom);
        $styles = $xpath->query('/html/head/style');
        $css = '';
        foreach ($styles as $style) $css .= $style->textContent;
        return ltrim($css);
    }

    protected function _styles()
    {
        $styles = $this->_queryAttr('/html/head/link[@rel="stylesheet"]/@href');
        foreach ($styles as &$style) $style = $this->_makeUrl($style);
        return $styles;
    }

    protected function _scripts()
    {
        $scripts = $this->_queryAttr('/html/head/script/@src');
        foreach ($scripts as &$script) $script = $this->_makeUrl($script);
        return $scripts;
    }

    protected function _body()
    {
        $body = $this->_dom->getElementsByTagName('body')->item(0);
        $imgs = $body->getElementsByTagName('img');
        foreach ($imgs as $img) {
            if (!$img->hasAttribute('src')) continue;
            $url = $img->getAttribute('src');
            $img->setAttribute('src', $this->_makeUrl($url));
        }
        $html = $this->_dom->saveHTML($body) . PHP_EOL;
        return str_ireplace(
            array('<body>', '</body>'),
            array('<div id="html-body">', '</div>'),
            $html
        );
    }

    protected function _queryAttr($str)
    {
        $xpath = new DOMXPath($this->_dom);
        $nodes = $xpath->query($str);
        $r = array();
        foreach ($nodes as $node) $r[] = $node->value;
        return $r;
    }

    protected function _makeUrl($url)
    {
        // ! absolute, protocol, inline, javascript
        if (strpos($url, ':') === false and !preg_match('/^\//', $url)) {
            return Sys::app()->fileUrl($this->_base . '/' . $url);
        }
        return $url;
    }
}
