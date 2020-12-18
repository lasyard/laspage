<?php
class FetchFile
{
    protected const METHOD = 'AES-128-CBC';
    protected const KEYLEN = 16;
    protected $_path;

    protected static function _ivKey()
    {
        $ivSize = openssl_cipher_iv_length(self::METHOD);
        return array(
            str_pad('laspage', self::KEYLEN, '*'), // key
            str_pad('laspage', $ivSize, '+'), // iv
        );
    }

    public static function encPath($path)
    {
        list($key, $iv) = self::_ivKey();
        $cipher = openssl_encrypt($path, self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
        return bin2hex($cipher);
    }

    public static function decPath($str)
    {
        list($key, $iv) = self::_ivKey();
        $cipher = hex2bin($str);
        return openssl_decrypt($cipher, self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
    }

    public function setCode($cipher)
    {
        $this->_path = @self::decPath($cipher);
    }

    public function path()
    {
        return $this->_path;
    }

    public function output()
    {
        $path = $this->_path;
        if (!isset($path) or !is_file($path)) throw new Exception("File code is invalid!");
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ($ext == 'js') {
            $type = 'text/javascript';
        } else {
            $ff = finfo_open(FILEINFO_MIME_TYPE);
            $type = finfo_file($ff, $path);
            finfo_close($ff);
        }
        Sys::app()->enableCache($path);
        header('Content-Type: ' . $type);
        header('Accept-Ranges: bytes');
        header('Accept-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Transfer-Encoding: binary');
        readfile($path);
    }
}
