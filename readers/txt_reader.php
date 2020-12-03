<?php
class TxtReader extends Reader
{
    protected $_content;
    protected $_title;

    public function load($file)
    {
        $this->_content = file($file, FILE_IGNORE_NEW_LINES);
        $this->_title = array_shift($this->_content);
    }

    protected function _title()
    {
        return $this->_title;
    }

    protected function _body()
    {
        $html = '';
        $html .= '<h1>' . $this->_title . '</h1>' . PHP_EOL;
        $pOpen = false;
        foreach ($this->_content as $line) {
            if (trim($line) == '') {
                if ($pOpen) {
                    $html .= "</p>" . PHP_EOL;
                    $pOpen = false;
                }
            } else {
                if (!$pOpen) {
                    $html .= "<p>";
                    $pOpen = true;
                } else {
                    $html .= "<br />" . PHP_EOL;
                }
                $html .= $this->_filterLinks(htmlspecialchars($line));
            }
        }
        if ($pOpen) {
            $html .= "</p>" . PHP_EOL;
            $pOpen = false;
        }
        $html = $this->_markH($html);
        $html = $this->_markAuthor($html);
        return $html;
    }

    protected function _filterLinks($txt)
    {
        return preg_replace(
            '/(?:ftp|https?):\/\/[^\)\s]*/',
            '<a href="$0" target="_blank">$0</a>',
            $txt
        );
    }

    protected function _markH($html)
    {
        // comma, period, colon, exclamation, question
        $notPunc = '[^\x{FF0C}\x{3002}\x{FF1A}\x{FF01}\x{FF1F},.:!?;=<>_]';
        $html = preg_replace_callback(
            '/^<p>(\d+(?:\.\d+)*\.?)\s+(' . $notPunc . '+)<\/p>$/mu',
            function ($m) use (&$lv) {
                $lv = 2 + count(explode('.', rtrim($m[1], '.'))) - 1;
                $openTag = '<h' . $lv . '>';
                $closeTag = '</h' . $lv . '>';
                return $openTag . $m[1] . ' ' . $m[2] . $closeTag;
            },
            $html
        );
        return $html;
    }

    protected function _markAuthor($html)
    {
        $html = preg_replace(
            array(
                '/^<p>(Author:.*)<\/p>$/m',
                '/^<p>(\x{4F5C}\x{8005}\x{FF1A}.*)<\/p>$/mu'
            ),
            array(
                '<p><i>$1</i></p>',
                '<p><i>$1</i></p>'
            ),
            $html
        );
        return $html;
    }
}
