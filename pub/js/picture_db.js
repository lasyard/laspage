(function (D) {
    function _newField(key, value) {
        var e = D.createElement('input');
        e.name = key;
        e.type = 'text';
        e.value = value;
        return e;
    }

    function _newHiddenForm(fields) {
        var form = D.createElement('form');
        form.name = '__hidden_form_';
        form.method = 'POST';
        form.style.display = 'none';
        for (var key in fields) {
            form.appendChild(_newField(key, fields[key]));
        }
        D.body.appendChild(form);
        return form;
    }

    window.addEventListener('load', function () {
        var imgs = D.querySelectorAll('div#content img');
        imgs.forEach(function (img) {
            var fn = img.getAttribute('fn');
            img.addEventListener('click', function () {
                var title = prompt('Input new title for image file "' + fn + '". "----" for deletion.');
                if (title) {
                    _newHiddenForm({ fileName: fn, title: title }).submit();
                }
            });
        });
    });
})(document);
