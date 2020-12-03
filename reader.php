<?php
abstract class Reader
{
    protected $_cache = array();

    public abstract function load($file);

    public function __get($name)
    {
        $key = "_$name";
        if (!isset($this->_cache[$key])) {
            if (method_exists($this, $key)) {
                $this->_cache[$key] = $this->{$key}();
            } else {
                $this->_cache[$key] = NULL;
            }
        }
        return $this->_cache[$key];
    }

    protected function _httpHeaders()
    {
        return array(
            'Content-type: text/html',
            'Cache-Control: public, max-age=259200',
        );
    }
}
