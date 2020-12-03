<?php
if (isset($date)) {
    if (isset($fileInfo)) {
?>
        <p>日期：<?php echo $date; ?> - <?php echo $fileInfo; ?></p>
    <?php
    } else {
    ?>
        <p>日期：<?php echo $date; ?></p>
    <?php
    }
} else if (isset($fileInfo)) {
    ?>
    <p><?php echo $fileInfo; ?></p>
<?php
}
