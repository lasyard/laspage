<?php
$arrowLeft = '<i class="fas fa-angle-double-left"></i>';
$arrowRight = '<i class="fa fa-angle-double-right"></i>';
?>
<div class="sys center">
    <span class="box">
        <?php
        if ($from > 0) {
            echo Html::link($arrowLeft, $url($page - 1));
        } else {
            echo $arrowLeft;
        }
        ?>
    </span>&nbsp;
    <?php
    for ($pg = 0; $pg <= $totalPages; $pg++) {
    ?>
        <span class="box">
            <?php
            if ($pg == $page) {
                echo '<span class="black">', $pg + 1, '</span>';
            } else {
                echo Html::link($pg + 1, $url($pg));
            }
            ?>
        </span>&nbsp;
    <?php
    }
    ?>
    <span class="box">
        <?php
        if ($to < $count) {
            echo Html::link($arrowRight, $url($page + 1));
        } else {
            echo $arrowRight;
        }
        ?>
    </span>&nbsp;
    <span class="box">
        <?php
        echo ($from + 1), ' - ', ($to), ' of ', $count;
        ?>
    </span>
</div>
