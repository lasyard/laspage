<?php
abstract class Reader
{
    protected $_cache = array();
    protected $_dict = array();

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
            'Cache-Control: no-cache, no-store, must-revalidate',
            'Expires: 0',
        );
    }

    public function addDict($dict)
    {
        $this->_dict = array_merge($this->_dict, $dict);
    }

    protected function tr($str)
    {
        $dict = $this->_dict;
        foreach ($dict as $key => $value) {
            $str = preg_replace("/ *\b$key\b */", '<span style="color:magenta">' . $value . '</span>', $str);
        }
        return $str;
    }
}
