<?php
class DictReader extends Reader
{
    protected $_dict;

    public function load($file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            list($key, $value) = explode("\t", $line, 2);
            $this->_dict[$key] = '<span style="color:magenta">' . $value . '</span>';
        }
    }

    protected function _dict()
    {
        return $this->_dict;
    }

    protected function _body()
    {
        $html = '<table>' . PHP_EOL;
        $html .= '<colgroup><col /><col /></colgroup>' . PHP_EOL;
        $html .= '<tr><th>Key</th><th>Value</th></tr>' . PHP_EOL;
        foreach ($this->_dict as $key => $value) {
            $html .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>' . PHP_EOL;
        }
        $html .= '</table>' . PHP_EOL;
        return $html;
    }
}
