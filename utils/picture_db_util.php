<?php
class PictureDbUtil
{
    protected $_db;
    protected $_accepted;
    protected $_jdb;

    public function db($category, $type = 'jpg')
    {
        $this->_db = new PictureDb($category, $type);
        if ($type == 'jpg') {
            $this->_accept = 'image/jpeg';
        } else {
            Sys::app()->error('Picture type "' . $type . '" is not supported!');
        }
        $this->_jdb = new JsonDb($category);
        return $this;
    }

    public function form($title, $sizeLimit = 1000000)
    {
        if (!Sys::app()->canEdit) return $this;
        $args = array(
            'title' => $title,
            'sizeLimit' => $sizeLimit,
            'field' => array('name' => 'picture', 'accept' => $this->_accepted),
        );
        $args['auxFields'] = array(array('name' => 'title'));
        Sys::render('file_upload_form', $args);
        return $this;
    }

    public function view($page, $baseUrl, $picsPerPage = 12)
    {
        $app = Sys::app();
        if ($app->canEdit) {
            $app->addScript('picture_db');
        }
        $app->addStyle('pictures');
        $files = $this->_db->files();
        $args = array(
            'app' => $app,
            'files' => $files,
            'page' => $page,
            'baseUrl' => $baseUrl,
            'picsPerPage' => $picsPerPage,
        );
        $args['titles'] = $this->_jdb->getAll();
        Sys::render('picture_db_view', $args);
        return $this;
    }

    public function save()
    {
        $app = Sys::app();
        if (!$app->canEdit) $app->error('Privileges required!');
        $db = $this->_db;
        $jdb = $this->_jdb;
        if (isset($_POST['fileName']) && isset($_POST['title'])) {
            $fileName = $_POST['fileName'];
            $title = $_POST['title'];
            if ($title === '----') {
                $db->del($fileName);
                $jdb->del($fileName);
            } else {
                $jdb->put($fileName, $title);
            }
            return;
        }
        $pic = $_FILES['picture'];
        if (empty($pic['name'])) $app->error('No file selected!');
        if ($pic['error']) $app->error('Upload file error!');
        if (!is_uploaded_file($pic['tmp_name'])) $app->error('Upload file error!');
        $path = $db->path($pic['tmp_name'], pathinfo($pic['name'], PATHINFO_FILENAME));
        if (!$path) $app->error('File has no EXIF data or date hint!');
        PictureTool::optimizeJpegFile($pic['tmp_name'], $path);
        if (isset($_POST['title'])) {
            $jdb->put(pathinfo($path, PATHINFO_FILENAME), $_POST['title']);
        }
    }

    public static function frontEnd($args, $options)
    {
        extract($options);
        $pdu = new PictureDbUtil();
        $pdu->db($dbName);
        if (!empty($_POST)) {
            $pdu->save();
        }
        $pdu->form($formTitle);
        $page = empty($args) ? 0 : $args[0];
        $pdu->view($page, $urlBase);
    }
}
