<?php
class LocalStream
{
    protected $_c;

    public function dir_opendir($path, $options)
    {
        $this->_c = @opendir($this->_path($path));
        return $this->_c ? true : false;
    }

    public function dir_readdir()
    {
        return readdir($this->_c);
    }

    public function dir_closedir()
    {
        closedir($this->_c);
        return true;
    }

    public function dir_rewinddir()
    {
        rewinddir($this->_c);
        return true;
    }

    public function url_stat($path, $flags)
    {
        return @stat($this->_path($path));
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->_c = @fopen($this->_path($path), $mode);
        return $this->_c ? true : false;
    }

    public function stream_stat()
    {
        return fstat($this->_c);
    }

    public function stream_read($count)
    {
        return fread($this->_c, $count);
    }

    public function stream_eof()
    {
        return feof($this->_c);
    }

    public function stream_close()
    {
        fclose($this->_c);
    }

    public function stream_cast($castAs)
    {
        return $this->_c;
    }

    public function stream_set_option(int $option, int $arg1, int $arg2)
    {
        switch ($option) {
            case STREAM_OPTION_BLOCKING:
                return stream_set_blocking($this->_c, $arg1);
            case STREAM_OPTION_READ_TIMEOUT:
                return stream_set_timeout($this->_c, $arg1, $arg2);
            case STREAM_OPTION_WRITE_BUFFER:
                if ($arg1 == STREAM_BUFFER_FULL) {
                    return stream_set_write_buffer($this->_c, $arg2) == 0;
                } else {
                    return stream_set_write_buffer($this->c, 0) == 0;
                }
        }
        return false;
    }

    protected function _path($path)
    {
        return str_replace(STREAM_PROTOCOL . '://', DATA_PATH, $path);
    }
};
