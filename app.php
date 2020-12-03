<?php
require_once 'base.php';

class App extends Base
{
    protected $_title;
    protected $_info;
    protected $_cssText = '';
    protected $_cssFiles = array();
    protected $_globalDatum = array();
    protected $_scriptFiles = array();
    protected $_breadcrumbs = array();
    protected $_fileLinks = array();
    protected $_relatedLinks = array();
    protected $_db;
    protected $_user;
    protected $_base;
    protected $_closeOnError = true;
    protected $_baseUrl;
    protected $_isRoot;

    public function __construct()
    {
        date_default_timezone_set("UTC");
        define('CONF_PATH', ROOT_PATH . '/configs');
        foreach (glob(CONF_PATH . '/*.php') as $configFile) {
            require_once $configFile;
        }
        if (!defined('SCENE')) define('SCENE', 'default');
        if (!defined('VIEWS_DIR')) define('VIEWS_DIR', 'views');
        if (!defined('DATA_DIR')) define('DATA_DIR', 'data');
        if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', 'upload');
        if (!defined('UTILS_DIR')) define('UTILS_DIR', 'utils');
        if (!defined('PUB_DIR')) define('PUB_DIR', 'pub');
        define('VIEWS_PATH', ROOT_PATH . '/' . VIEWS_DIR);
        define('DATA_PATH', ROOT_PATH . '/' . DATA_DIR);
        define('UPLOAD_PATH', ROOT_PATH . '/' . UPLOAD_DIR);
        define('UTILS_PATH', ROOT_PATH . '/' . UTILS_DIR);
        define('PUB_PATH', ROOT_PATH . '/' . PUB_DIR);
        set_include_path(get_include_path()
            . PATH_SEPARATOR . __DIR__
            . PATH_SEPARATOR . __DIR__ . '/readers'
            . PATH_SEPARATOR . __DIR__ . '/db'
            . PATH_SEPARATOR . __DIR__ . '/utils'
            . PATH_SEPARATOR . __DIR__ . '/wrappers'
            . PATH_SEPARATOR . __DIR__ . '/libs'
            . PATH_SEPARATOR . UTILS_PATH);
        spl_autoload_register(function ($class) {
            $words = preg_split('/(?=[A-Z])/', $class, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($words as &$word) $word = strtolower($word);
            $file = implode('_', $words) . '.php';
            require_once $file;
        });
        define('STREAM_PROTOCOL', 'lasyard');
        stream_wrapper_register(STREAM_PROTOCOL, 'LocalStream');
        session_start();
    }

    public function run()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }
        $host = $_SERVER['HTTP_HOST'];
        $port = $_SERVER['SERVER_PORT'];
        $this->_base = $protocol . '://' . $host;
        if ($protocol == 'http' && $port != 80 || $protocol == 'https' && $port != 443) {
            $this->_base += ':' . $port;
        }
        $uri = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
        $prefix = dirname($_SERVER['PHP_SELF']);
        if (substr($prefix, -1) != '/') $prefix .= '/';
        if (strpos($uri, $prefix) === 0) {
            $uri = substr_replace($uri, '', 0, strlen($prefix));
            $this->_base .= $prefix;
        }
        $path = explode('/', $uri);
        if ($path[0] == 'file') $this->echoFile($path[1]);
        $this->addScript('main');
        $this->tryAddScript('main_' . SCENE);
        $this->addStyle('main');
        $this->tryAddStyle('main_' . SCENE);
        $this->addStyle('//cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.1/css/all.min.css');
        $node = new Node;
        try {
            while (!empty($path) && $node->isDir) {
                $name = array_shift($path);
                $node->rollTo($name);
            }
            if (!$node->resolved) $this->redirect($node->baseUrl);
            $node->cook($path);
            $content = $node->content;
        } catch (Exception $e) {
            $this->_title = 'Error';
            $this->_info = 'Error';
            $content = Sys::renderHtml('error', array(
                'message' => $e->getMessage(),
            ));
        }
        $httpHeaders = $node->httpHeaders;
        if (!empty($httpHeaders)) {
            if (is_array($httpHeaders)) {
                foreach ($httpHeaders as $header) header($header);
            } else {
                header($httpHeaders);
            }
        }
        $this->_baseUrl = $node->baseUrl;
        $this->_isRoot = $node->isRoot;
        Sys::render('main', array(
            'content' => $content,
        ));
    }

    public function addCssText($text)
    {
        $this->_cssText .= $text;
    }

    public function db()
    {
        if (!isset($this->_db)) {
            $this->_db = new Db;
        }
        return $this->_db;
    }

    public function user()
    {
        if (!isset($this->_user)) {
            if (!isset($_SESSION['user'])) {
                if (isset($_COOKIE['name']) && isset($_COOKIE['password'])) {
                    $this->_setUser($_COOKIE['name'], $_COOKIE['password']);
                } else {
                    return null;
                }
            }
            $this->_user = $_SESSION['user'];
        }
        return $this->_user;
    }

    public function setUser($post)
    {
        if (isset($_SESSION['user'])) {
            $this->_user = null;
            session_unset();
            session_destroy();
            session_write_close();
            unset($_COOKIE['name']);
            unset($_COOKIE['password']);
            setcookie(session_name(), '', 0, '/');
            setcookie('name', '', 0, '/');
            setcookie('password', '', 0, '/');
        }
        if (isset($post['name']) && isset($post['password'])) {
            $this->_setUser($post['name'], $post['password']);
        }
    }

    private function _setUser($name, $password)
    {
        $db = $this->db;
        if (!$db) return;
        $tbl = $db->tbl('user');
        $user = $tbl->selectById($name);
        if (!$user) $this->error("Wrong username or password!");
        $hash = $user['password_hash'];
        if ($hash === crypt($password, $hash)) {
            $_SESSION['user'] = array(
                'name' => $name,
                'priv' => explode(',', $user['priv']),
            );
            $expire = time() + 60 * 60 * 24 * 30;
            setcookie('name', $name, $expire, '/');
            setcookie('password', $password, $expire, '/');
        } else {
            $this->error("Wrong username or password!");
        }
    }

    public function hasPriv($privDefs)
    {
        foreach (explode(' ', $privDefs) as $privDef) {
            @list($priv, $scene) = explode('@', $privDef, 2);
            if (!empty($priv)) {
                $user = $this->user;
                if (empty($user)) continue;
                $privs = $user['priv'];
                if (!in_array($priv, $privs)) continue;
            }
            if (!empty($scene)) {
                if ($scene != SCENE) continue;
            }
            return true;
        }
        return false;
    }

    public function __call($name, $args)
    {
        if (substr($name, 0, 3) == 'can') {
            $priv = lcfirst(substr($name, 3));
            return $this->hasPriv($priv);
        }
        return parent::__call($name, $args);
    }

    public function addRelatedLink($link)
    {
        if (substr($link['url'], 0, 1) == '/') {
            $link['url'] = substr_replace($link['url'], $this->_base, 0, 1);
        }
        $this->_relatedLinks[] = $link;
    }

    public function redirect($url)
    {
        header("Location: $url");
        $this->end();
    }

    public function back()
    {
        $this->redirect($_SERVER['HTTP_REFERER']);
    }

    public function gotoPage($path)
    {
        $this->redirect($this->_base . $path);
    }

    public function end()
    {
        exit;
    }

    public function error($msg)
    {
        if ($this->_closeOnError) ob_end_clean();
        throw new RuntimeException($msg);
    }

    public function ajaxEcho($msg)
    {
        echo $msg;
        $this->end();
    }

    public function fileUrl($path)
    {
        return $this->_base . 'file/' . FetchFile::encPath($path);
    }

    public function echoFile($code)
    {
        $f = new FetchFile;
        $f->setCode($code);
        $f->output();
        $this->end();
    }

    public function getReader($ext)
    {
        if (is_file(__DIR__ . '/readers/' . $ext . '_reader.php')) {
            $readerName = ucfirst($ext) . 'Reader';
            return new $readerName;
        }
        return NULL;
    }

    public function pubUrl($path)
    {
        return $this->_base . PUB_DIR . '/' . $path;
    }

    public function addStyle($file)
    {
        if ($this->tryAddStyle($file)) {
            return;
        }
        $path = 'css/' . $file . '.css';
        if (is_file(PUB_PATH . '/sys/' . $path)) {
            $this->addCssFile($this->pubUrl('sys/' . $path));
        } else {
            $this->addCssFile($file);
        }
    }

    public function tryAddStyle($file)
    {
        $path = 'css/' . $file . '.css';
        if (is_file(PUB_PATH . '/' . $path)) {
            $this->addCssFile($this->pubUrl($path));
            return true;
        }
        return false;
    }

    public function addGlobalData($data, $name, $encOptions = JSON_UNESCAPED_UNICODE)
    {
        $this->_globalDatum[] = array(
            'data' => $data,
            'name' => $name,
            'encOptions' => $encOptions,
        );
    }

    public function addScript($file)
    {
        if ($this->tryAddScript($file)) {
            return;
        }
        $path = 'js/' . $file . '.js';
        if (is_file(PUB_PATH . '/sys/' . $path)) {
            $this->addScriptFile($this->pubUrl('sys/' . $path));
        } else {
            $this->addScriptFile($file);
        }
    }

    public function tryAddScript($file)
    {
        $path = 'js/' . $file . '.js';
        if (is_file(PUB_PATH . '/' . $path)) {
            $this->addScriptFile($this->pubUrl($path));
            return true;
        }
        return false;
    }

    public function addInfoTip()
    {
        $this->addStyle('info_tip');
        $this->addScript('info_tip');
        Sys::render('info_tip');
    }

    public function header()
    {
        Sys::render('app_header');
    }
}
