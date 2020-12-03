class DbEditPanel {
    #recs;
    #keys;
    #formEdit;
    #formDel;

    constructor(recs, keys, options = {}) {
        if (!options.noEdit) {
            this.#recs = recs;
            this.#keys = {};
            for (const i in keys) {
                const key = keys[i];
                if (key == null) {
                    continue;
                }
                if (typeof key == 'object') {
                    this.#keys[i] = key;
                } else {
                    this.#keys[i] = {
                        def: key
                    };
                }
            }
            const editFormName = options.editFormName;
            const buttonsBoxId = options.buttonsBoxId;
            const editFormAction = options.editFormAction ? options.editFormAction : '';
            this.#setEditForm(editFormName, buttonsBoxId, editFormAction);
        }
        if (!options.noDelete) {
            if (!options.deleteFormAction) {
                options.deleteFormAction = '';
            }
            this.#setDelForm(options.deleteFormAction);
        }
    }

    resetValue() {
        const form = this.#formEdit;
        const recs = this.#recs;
        const keys = this.#keys;
        const id = form.elements['id'].value;
        for (const i in keys) {
            DbEditPanel.#setElement(form, i, recs[id][i], keys[i].callback);
        }
    }

    dup() {
        this.#cancel();
    }

    cancel() {
        this.#cancel();
        const form = this.#formEdit;
        const keys = this.#keys;
        for (var i in keys) {
            DbEditPanel.#setElement(form, i, keys[i].def, keys[i].callback);
        }
    }

    edit(id) {
        const form = this.#formEdit;
        if (!form) {
            return;
        }
        form.elements['id'].value = id;
        this.resetValue(form);
        form.elements['__btn_submit__'].value = 'Save';
        form.elements['__btn_reset__'].style.display = 'inline';
        form.elements['__btn_cancel__'].style.display = 'inline';
        form.elements['__btn_dup__'].style.display = 'inline';
        scrollTo(0, 0);
    }

    del(id) {
        if (!confirm('Are you sure to delete item ' + id + '?')) {
            return;
        }
        const form = this.#formDel;
        form.elements['id'].value = id;
        form.submit();
    }

    #cancel() {
        const form = this.#formEdit;
        form.elements['id'].value = '';
        form.elements['__btn_submit__'].value = 'Add';
        form.elements['__btn_reset__'].style.display = 'none';
        form.elements['__btn_cancel__'].style.display = 'none';
        form.elements['__btn_dup__'].style.display = 'none';
    }

    #setEditForm(formName, buttonsBoxId, action) {
        const form = document.forms[formName];
        form.action = action;
        form.method = "POST";
        const self = this;
        const box = document.getElementById(buttonsBoxId);
        DbEditPanel.#addInput(box, 'submit', '__btn_submit__', 'Add');
        DbEditPanel.#addInput(box, 'button', '__btn_reset__', 'Reset', true).onclick = () => self.resetValue();
        DbEditPanel.#addInput(box, 'button', '__btn_cancel__', 'Cancel', true).onclick = () => self.cancel();
        DbEditPanel.#addInput(box, 'button', '__btn_dup__', 'Dup', true).onclick = () => self.dup();
        this.#formEdit = form;
    }

    #setDelForm(action) {
        const form = document.createElement('form');
        form.name = '__del_form__';
        form.style.display = 'none';
        DbEditPanel.#addInput(form, 'text', 'id');
        DbEditPanel.#addInput(form, 'checkbox', 'DEL').checked = true;
        form.method = 'POST';
        form.action = action;
        document.body.appendChild(form);
        this.#formDel = form;
    }

    static #addInput(parent, type, name, value = null, hide = false) {
        const e = document.createElement('input');
        e.type = type;
        e.name = name;
        if (value) {
            e.value = value;
        }
        if (hide) {
            e.style.display = 'none';
        }
        parent.appendChild(e);
        return e;
    }

    static #setElement(form, key, value, callback) {
        const e = form.elements[key];
        if (typeof e != 'undefined') {
            if (e instanceof HTMLInputElement && e.type == 'checkbox') {
                e.checked = (value == '1' ? true : false);
            } else {
                e.value = value;
            }
        }
        if (typeof callback == 'function') {
            callback.call(form, value);
        }
    }
}
