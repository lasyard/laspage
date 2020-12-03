<?php
class VirtualReader extends Reader
{
    protected $_text;

    public function load($text)
    {
        $this->_text = $text;
    }

    protected function _body()
    {
        return '<div class="error">' . $this->_text . '</div>' . PHP_EOL;
    }

    protected function _httpHeaders()
    {
        return array(
            'Content-type: text/html',
            'Cache-Control: no-cache',
        );
    }
}
