<?php
if (empty($editPanelOptions['noEdit'])) {
?>
    <fieldset>
        <legend><?php echo $editFormTitle; ?></legend>
        <form name="<?php echo $editPanelOptions['editFormName']; ?>">
            <?php
            echo Html::input('text', 'id', array('style' => 'display:none'));
            foreach ($editFormFields as $fn => $field) {
                $field['name'] = $fn;
                Sys::render('form_field', $field);
            }
            ?>
            <div class="center" id="<?php echo $editPanelOptions['buttonsBoxId'] ?>"></div>
        </form>
    </fieldset>
<?php
}
?>
<script type="text/javascript">
    var __edit_panel__ = new DbEditPanel(
        __dataset__,
        <?php echo $editPanelKeys; ?>,
        <?php echo json_encode($editPanelOptions, JSON_FORCE_OBJECT); ?>
    );
    <?php
    if (empty($editPanelOptions['noEdit'])) {
    ?>
        window.addEventListener('load', function() {
            __edit_panel__.cancel();
        });
    <?php
    }
    ?>
</script>
