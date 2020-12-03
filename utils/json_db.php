<?php
class JsonDb
{
    const _DIR = '/json_db/';

    protected $_fileName;
    protected $_text;
    protected $_data;

    public function __construct($fileName)
    {
        $this->_fileName = $fileName;
        $path = $this->_path();
        if (!is_file($path)) {
            file_put_contents($path, '{ }');
        }
        $this->_text = file_get_contents($path);
    }

    protected function _get()
    {
        if ($this->_data === null) {
            $text = $this->_text;
            if ($text) {
                $this->_data = json_decode($text, true);
            } else {
                $this->_data = array();
            }
        }
        return $this->_data;
    }

    protected function _save()
    {
        $text = json_encode($this->_data, JSON_PRETTY_PRINT);
        file_put_contents($this->_path(), $text);
        $this->_text = $text;
    }

    public function getJson()
    {
        return $this->_text;
    }

    public function getAll()
    {
        return $this->_get();
    }

    public function get($key)
    {
        $data = $this->_get();
        if (key_exists($data, $key)) {
            return $data[$key];
        }
        return null;
    }

    public function del($key)
    {
        $data = $this->_get();
        unset($data[$key]);
        $this->_data = $data;
        $this->_save();
    }

    public function put($key, $value)
    {
        $data = $this->_get();
        $data[$key] = $value;
        $this->_data = $data;
        $this->_save();
    }

    protected function _path()
    {
        return UPLOAD_PATH . self::_DIR . $this->_fileName . '.json';
    }
}
