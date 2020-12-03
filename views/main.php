<!DOCTYPE html>
<?php
$app = Sys::app();
$base = $app->base;
$baseUrl = $app->baseUrl;
$isRoot = $app->isRoot;
$title = $app->title;
$fileLinks = $app->fileLinks;
$relatedLinks = $app->relatedLinks;
$breadcrumbs = $app->breadcrumbs;
$info = $app->info;
$user = $app->user;
?>
<html lang="zh-cn">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LasPage<?php echo empty($title) ? '' : ' - ' . $title; ?></title>
    <?php
    Sys::app()->header();
    ?>
</head>

<body>
    <div id="bar">
        <span><?php if ($user) echo '<a href="' . $base . 'logout" class="sys" style="float:right"><i class="fas fa-user"></i> ', $user['name'], '</a>'; ?></span>
    </div>
    <div id="list">
        <fieldset>
            <legend><i class="fas fa-link red"></i> 文件列表</legend>
            <ul>
                <?php
                foreach ($fileLinks as $link) {
                    $linkOpts = array();
                    if (!empty($link['info'])) $linkOpts['title'] = $link['info'];
                    $suf = '';
                    if ($link['isDir']) $suf = ' <i class="fas fa-angle-double-right" style="float:right"></i>';
                    $liOpts = array();
                    if ($link['selected']) $liOpts['class'] = 'highlighted';
                    echo Html::li(Html::link($link['title'], $link['url'], $linkOpts) . $suf, $liOpts) . PHP_EOL;
                }
                ?>
            </ul>
            <?php
            if (!$isRoot) {
            ?>
                <p><a href="<?php echo dirname($baseUrl) . '/'; ?>" class="sys"><i class="fas fa-angle-double-left"></i> 返回</a></p>
            <?php
            }
            ?>
        </fieldset>
        <?php
        if (count($relatedLinks) > 0) {
        ?>
            <fieldset>
                <legend><i class="fas fa-external-link-alt red"></i> 相关链接</legend>
                <ul>
                    <?php
                    foreach ($relatedLinks as $link) {
                        $linkOpts = array();
                        if (!empty($link['info'])) $linkOpts['title'] = $link['info'];
                        if (isset($link['target'])) $linkOpts['target'] = $link['target'];
                        echo Html::li(Html::link($link['title'], $link['url'], $linkOpts)) . PHP_EOL;
                    }
                    ?>
                </ul>
            </fieldset>
        <?php
        }
        ?>
    </div>
    <div id="content">
        <div id="breadcrumb">
            <?php
            foreach ($breadcrumbs as $b) {
                echo ' <i class="fas fa-angle-double-right"></i> ' . Html::link($b['title'], $b['url']);
            }
            echo PHP_EOL;
            ?>
        </div>
        <?php if (!empty($info)) { ?>
            <div id="info"><?php echo $info; ?></div>
        <?php } ?>
        <div id="main"><?php echo $content; ?></div>
    </div>
</body>

</html>
