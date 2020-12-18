<?php
class PhpReader extends Reader
{
    protected $_file;
    protected $_body;
    protected $_htmlReader = null;

    public function load($file)
    {
        $this->_file = $file;
    }

    public function cookArgs($baseUrl, $args)
    {
        $app = Sys::app();
        $file = $this->_file;
        ob_start();
        include $file;
        $body = ob_get_clean();
        if (stripos($body, '<!DOCTYPE html') !== false) {
            $this->_htmlReader = new HtmlReader;
            $base = substr($file, 0, strrpos($file, '/'));
            $this->_htmlReader->loadStr($base, $body);
        } else {
            $this->_body = $body;
        }
    }

    protected function _title()
    {
        if ($this->_htmlReader) {
            return $this->_htmlReader->title;
        } else {
            return null;
        }
    }

    protected function _cssText()
    {
        if ($this->_htmlReader) {
            return $this->_htmlReader->cssText;
        } else {
            return null;
        }
    }

    protected function _styles()
    {
        if ($this->_htmlReader) {
            return $this->_htmlReader->styles;
        } else {
            return null;
        }
    }

    protected function _scripts()
    {
        if ($this->_htmlReader) {
            return $this->_htmlReader->scripts;
        } else {
            return null;
        }
    }

    protected function _body()
    {
        if ($this->_htmlReader) {
            return $this->_htmlReader->body;
        } else {
            return $this->_body;
        }
    }
}
