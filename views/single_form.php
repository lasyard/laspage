<?php
if (!isset($method)) $method = 'GET';
?>
<fieldset>
    <legend><?php echo $title; ?></legend>
    <form action="<?php echo $action; ?>" method="<?php echo $method; ?>">
        <?php
        Sys::render('form_field', $field);
        ?>
        <div class="center"><input type="submit" value="Submit" /></div>
    </form>
</fieldset>
