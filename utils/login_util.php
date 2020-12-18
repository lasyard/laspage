<?php
class LoginUtil
{
    public function __construct($app)
    {
        $app->addScript('https://cdn.jsdelivr.net/npm/crypto.js@2.0.2/index.min.js');
        $app->addScript('login_util');
    }

    public function beginForm($action = '')
    {
        echo '<form action="', $action, '" method="POST" onsubmit="encrypt(this)">' . PHP_EOL;
    }

    public function endForm()
    {
        echo '</form>' . PHP_EOL;
    }
}
