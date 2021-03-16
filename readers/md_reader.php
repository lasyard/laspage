<?php
require_once 'parsedown/Parsedown.php';

class MdReader extends Reader
{
    protected $_file;

    public function load($file)
    {
        $this->_file = $file;
    }

    protected function _body()
    {
        $file = $this->_file;
        $parsedown = new Parsedown;
        $html = $parsedown->text($this->tr(file_get_contents($file))) . PHP_EOL;
        $htmlReader = new HtmlReader;
        $base = substr($file, 0, strrpos($file, '/'));
        $htmlReader->loadStrEnc($base, $html, 'utf-8');
        $body = $htmlReader->body;
        $body .= '<script>hljs.initHighlightingOnLoad();</script>' . PHP_EOL;
        return $body;
    }

    protected function _styles()
    {
        return array(
            'https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@10.5.0/build/styles/default.min.css'
        );
    }

    protected function _scripts()
    {
        return array(
            'https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@10.5.0/build/highlight.min.js'
        );
    }
}
