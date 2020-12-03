<?php
class Thumb
{
    protected $im = null;
    protected $size;

    public function __construct($size = 20, $imageFile = null)
    {
        $this->size = $size;
        $this->im = imagecreatetruecolor($size, $size);
        $this->_setFile($imageFile);
    }

    public function __destruct()
    {
        imagedestroy($this->im);
    }

    public function output()
    {
        imagejpeg($this->im);
    }

    protected function _setFile($imageFile)
    {
        if (is_file($imageFile)) {
            $imOrig = imagecreatefromjpeg($imageFile);
            $sx = imagesx($imOrig);
            $sy = imagesy($imOrig);
            $s = min($sx, $sy);
            if (!imagecopyresampled(
                $this->im,
                $imOrig,
                0,
                0,
                ($sx - $s) / 2,
                ($sy - $s) / 2,
                $this->size,
                $this->size,
                $s,
                $s
            )) {
                imagedestroy($imOrig);
                unset($imOrig);
            }
        }
        if (!isset($imOrig)) {
            $grey = imagecolorallocate($this->im, 192, 192, 192);
            imagefill($this->im, 0, 0, $grey);
        } else {
            imagedestroy($imOrig);
        }
    }
}
