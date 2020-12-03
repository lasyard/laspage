<?php
$app = Sys::app();
$globalDatum = $app->globalDatum;
$scriptFiles = $app->scriptFiles;
$cssFiles = $app->cssFiles;
$cssText = $app->cssText;
$baseUrl = $app->baseUrl;
?>
<script>
    <?php
    foreach ($globalDatum as $globalData) {
        echo 'const ' . $globalData['name'] . ' = ' . json_encode($globalData['data'], $globalData['encOptions']) . ';' . PHP_EOL;
    }
    ?>

    function baseUrl() {
        return '<?php echo $baseUrl ?>';
    }

    function canEdit() {
        return <?php echo $app->canEdit ? 'true' : 'false'; ?>;
    }
</script>
<?php
foreach ($scriptFiles as $script) {
    echo Html::scriptLink($script);
}
foreach ($cssFiles as $css) {
    echo Html::cssLink($css);
}
if (!empty($cssText)) {
?>
    <style type="text/css">
        <?php echo $cssText; ?>
    </style>
<?php
}
