<?php
if (!isset($label) && isset($name)) $label = ucfirst($name);
if (!isset($type)) $type = 'text';
if (!isset($attrs)) $attrs = array();

if ($type == 'yearSelect') {
    $type = 'select';
    if (!isset($to)) $to = getdate()['year'];
    if (!isset($from)) $from = 1980;
    $years = range($to, $from, -1);
    $options = array_combine($years, $years);
}

?>
<div class="field">
    <div class="label"><?php echo isset($label) ? $label : ''; ?></div>
    <?php
    if ($type == 'select') {
    ?>
        <select name="<?php echo $name; ?>">
            <?php
            foreach ($options as $option => $value) {
                if (is_int($option)) $option = $value;
            ?>
                <option value="<?php echo $value; ?>"><?php echo $option; ?></option>
            <?php
            }
            ?>
        </select>
    <?php
    } else if ($type == 'checkbox-group') {
    ?>
        <div class="checkbox-group">
            <?php
            foreach ($checkboxes as $index => $checkbox) {
            ?>
                <span class="checkbox">
                    <input type="checkbox" name="<?php echo $name . '[' . $index . ']'; ?>" /><?php echo $checkbox; ?>
                </span>
            <?php
            }
            ?>
        </div>
    <?php
    } else if ($type == 'file') {
        if (isset($accept)) $attrs['accept'] = $accept;
        echo Html::input($type, $name, $attrs);
    } else {
        if (isset($value)) $attrs['value'] = $value;
        echo Html::input($type, $name, $attrs);
    }
    ?>
</div>
