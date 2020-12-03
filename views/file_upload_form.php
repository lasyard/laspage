<fieldset>
    <legend><?php echo $title; ?></legend>
    <form enctype="multipart/form-data" action="<?php echo isset($action) ? $action : ''; ?>" method="POST">
        <?php
        echo Html::input('hidden', 'MAX_FILE_SIZE', array('value' => $sizeLimit));
        $field['type'] = 'file';
        Sys::render('form_field', $field);
        if (isset($auxFields)) {
            foreach ($auxFields as $auxField) {
                Sys::render('form_field', $auxField);
            }
        }
        ?>
        <div class="center"><input type="submit" value="Upload" /></div>
    </form>
</fieldset>
