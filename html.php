<?php
class Html
{
    public static function cssLink($css)
    {
        return '<link rel="stylesheet" href="' . $css . '" type="text/css" />' . PHP_EOL;
    }

    public static function scriptLink($script)
    {
        return '<script type="text/javascript" src="' . $script . '"></script>' . PHP_EOL;
    }

    public static function link($title, $url = '', $attrs = array())
    {
        if (!empty($url)) {
            $html = '<a href="' . $url . '"';
            foreach ($attrs as $key => $value) $html .= ' ' . $key . '="' . $value . '"';
            $html .= '>' . $title . '</a>';
            return $html;
        } else {
            return $title;
        }
    }

    public static function li($content, $attrs = array())
    {
        $html = '<li';
        foreach ($attrs as $key => $value) $html .= ' ' . $key . '="' . $value . '"';
        $html .= '>' . $content . '</li>';
        return $html;
    }

    public static function input($type, $name, $attrs = array())
    {
        if ($type == 'textarea') {
            $html = '<textarea name="' . $name . '"';
            foreach ($attrs as $key => $value) $html .= ' ' . $key . '="' . $value . '"';
            $html .= '></textarea>';
        } else {
            $html = '<input type="' . $type . '" name="' . $name . '"';
            foreach ($attrs as $key => $value) $html .= ' ' . $key . '="' . $value . '"';
            $html .= ' />';
        }
        return $html;
    }
}
