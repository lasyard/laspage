<?php
class PictureDb
{
    const _DIR = '/picture_db/';

    protected $category;
    protected $type;

    public function __construct($category, $type)
    {
        $this->category = $category;
        $this->type = $type;
    }

    public function fileDir()
    {
        $dir = UPLOAD_PATH . self::_DIR . $this->category . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public function del($fileName)
    {
        unlink($this->fileDir() . $fileName . '.' . $this->type);
    }

    public function fileName($file, $dateHint)
    {
        $date = self::getExifDate($file);
        if (!$date) {
            $date = substr($dateHint, 0, 6);
            if (!$date or !self::isValidDate($date)) return false;
        }
        return $date . '_' . bin2hex(openssl_random_pseudo_bytes(4)) . '.' . $this->type;
    }

    public function files()
    {
        $files = glob($this->fileDir() . '*.' . $this->type);
        rsort($files);
        return $files;
    }

    public function path($file, $dateHint)
    {
        $name = $this->fileName($file, $dateHint);
        if (!$name) return false;
        return $this->fileDir() . $name;
    }

    public static function getExifDate($file)
    {
        $info = exif_read_data($file, 'EXIF');
        if (!$info || !isset($info['DateTimeOriginal'])) return false;
        $time = $info['DateTimeOriginal'];
        $date = preg_replace('/\D/', '', substr($time, 2, 8));
        return $date;
    }

    protected static function isValidDate($date)
    {
        if (strlen($date) < 6) return false;
        if (preg_match('/\D/', $date)) return false;
        $var = intval(substr($date, 2, 2)); // month
        if ($var < 1 || $var > 12) return false;
        $var = intval(substr($date, 4, 2)); // date
        if ($var < 1 || $var > 31) return false;
        return true;
    }
}
