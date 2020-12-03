<div id="__info_tip__">
    <div>
        <div><span id="__info_tip_title__" class="nobr"></span></div>
        <div><i id="__info_tip_close__" class="fas fa-window-close blue"></i></div>
    </div>
    <div id="__info_tip_body__"></div>
</div>
<script>
    const infoTip = new InfoTip(
        document.getElementById('__info_tip__'),
        document.getElementById('__info_tip_close__'),
        info => {
            document.getElementById('__info_tip_title__').innerHTML = info.title;
            document.getElementById('__info_tip_body__').innerHTML = info.body;
        }
    );
</script>
