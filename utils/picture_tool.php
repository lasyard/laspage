<?php
class PictureTool
{
    public static function optimizeJpegFile($old, $new)
    {
        if (extension_loaded('imagick')) {
            $img = new Imagick($old);
            $img->setImageCompressionQuality(80);
            $img->setOption('jpeg:optimize-coding', 'on');
            $img->setOption('jpeg:dct-method', 'islow');
            $img->stripImage();
            $img->writeImage($new);
        } else if (extension_loaded('gd')) {
            $img = imagecreatefromjpeg($old);
            imagejpeg($img, $new, 80);
            imagedestroy($img);
        }
    }
}
