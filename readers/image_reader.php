<?php
abstract class ImageReader extends Reader
{
    protected $_file;

    abstract public function __construct();

    public function load($file)
    {
        $this->_file = $file;
    }

    protected function _cssText()
    {
        return <<<'EOS'
div#content img {
    display: block;
    margin-top: 15px;
    margin-left: auto;
    margin-right: auto;
    border: 9px dashed #0F8;
}
EOS;
    }

    protected function _body()
    {
        return '<img src="' . Sys::app()->fileUrl($this->_file) . '" />' . PHP_EOL;
    }
}
