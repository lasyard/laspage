<?php
require_once 'app.php';

class Sys
{
    private static $_app;

    public static function app()
    {
        if (!isset(self::$_app)) self::$_app = new App;
        return self::$_app;
    }

    public static function renderHtml($layout, $vars = array())
    {
        ob_start();
        self::render($layout, $vars);
        return ob_get_clean();
    }

    public static function render($layout, $vars = array())
    {
        extract($vars);
        if (is_file(VIEWS_PATH . '/' . $layout . '.php')) {
            require VIEWS_PATH . '/' . $layout . '.php';
        } else if (is_file(__DIR__ . '/views/' . $layout . '.php')) {
            require 'views/' . $layout . '.php';
        } else {
            $info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            require dirname($info[0]['file']) . '/views/' . $layout . '.php';
        }
    }
}
