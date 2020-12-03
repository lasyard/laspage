<?php
class AnnexReader extends Reader
{
    protected $_file;

    public function load($file)
    {
        $this->_file = $file;
    }

    protected function _body()
    {
        $text = file_get_contents($this->_file);
        $html = '<pre class="annex">' . PHP_EOL;
        $html .= htmlspecialchars($text);
        $html .= '</pre>' . PHP_EOL;
        return $html;
    }
}
