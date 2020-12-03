<table class="picture">
    <tr>
        <td><img <?php if (!empty($fileName)) echo 'fn="', $fileName, '"'; ?> src="<?php echo $src; ?>" /></td>
    </tr>
    <?php
    if (isset($title)) {
    ?>
        <tr>
            <th><?php echo $title; ?></th>
        </tr>
    <?php
    }
    ?>
</table>
