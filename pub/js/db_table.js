class KeyFilter {
    _key;
    _title;
    _getLabel;
    _formName;
    _itemName;
    _items;

    constructor(key, title = null, getLabel = null) {
        this._key = key;
        this._title = title ? title : key.capitalize() + ' Filters';
        this._getLabel = getLabel ? getLabel : v => v;
    }

    attach(fun) {
        const items = document.forms[this._formName].elements[this._itemName];
        for (var i = 0; i < items.length; i++) {
            items[i].addEventListener('click', fun);
        }
        this._items = items;
    }

    _getValuesCount(recs) {
        var values = {};
        for (const id in recs) {
            const v = recs[id][this._key];
            if (!values[v]) {
                values[v] = 0;
            }
            values[v]++;
        }
        return values;
    }

    _sortByLabel(keys) {
        const self = this;
        return keys.sort((a, b) => strCmp(self._getLabel(a), self._getLabel(b)));
    }

    _pCheck(p, name, value, count, type = 'radio', checked = false) {
        p('<span class="checkbox">');
        p('<input name="' + name + '" value="' + value + '" type="' + type + '"'
            + (checked ? ' checked="checked"' : '') + '></input>');
        p('<label>' + this._getLabel(value) + ' </label><span class="red">(' + count + ')</span>');
        p('</span>');
    }
}

class KeyRadioFilter extends KeyFilter {
    render(p, recs) {
        const formName = DbTable.FILTER_FORM_NAME + '_radio_' + this._key;
        p('<fieldset>');
        p('<legend>' + this._title + '</legend>');
        p('<form name="' + formName + '">');
        const values = this._getValuesCount(recs);
        const itemName = formName + '_item';
        this._pCheck(p, itemName, 'All', Object.values(values).reduce((a, b) => a + b, 0), 'radio', true);
        const keys = this._sortByLabel(Object.keys(values));
        for (const v of keys) {
            this._pCheck(p, itemName, v, values[v]);
        }
        p('</form>');
        p('</fieldset>');
        this._formName = formName;
        this._itemName = itemName;
    }

    testFun() {
        const key = this._key;
        const items = this._items;
        return (rec) => items.value == 'All' || items.value == rec[key];
    }
}

class KeyCheckFilter extends KeyFilter {
    render(p, recs) {
        const formName = DbTable.FILTER_FORM_NAME + '_check_' + this._key;
        p('<fieldset>');
        p('<legend>' + this._title + '</legend>');
        p('<form name="' + formName + '">');
        const values = this._getValuesCount(recs);
        const itemName = formName + '_item';
        const keys = this._sortByLabel(Object.keys(values));
        for (const v of keys) {
            this._pCheck(p, itemName, v, values[v], 'checkbox', true);
        }
        p('</form>');
        p('</fieldset>');
        this._formName = formName;
        this._itemName = itemName;
    }

    testFun() {
        const key = this._key;
        const items = this._items;
        return rec => {
            for (const item of items) {
                if (item.value == rec[key] && item.checked) {
                    return true;
                }
            }
            return false;
        }
    }
}

class KeyMultiCheckFilter extends KeyFilter {
    render(p, recs) {
        const formName = '__genre_filter_check_' + this._key;
        p('<fieldset>');
        p('<legend>' + this._title + '</legend>');
        p('<form name="' + formName + '">');
        const values = this._getValuesCount(recs);
        const itemName = formName + '_item';
        const keys = this._sortByLabel(Object.keys(values));
        for (const v of keys) {
            this._pCheck(p, itemName, v, values[v], 'checkbox');
        }
        p('</form>');
        p('</fieldset>');
        this._formName = formName;
        this._itemName = itemName;
    }

    testFun() {
        const self = this;
        const items = this._items;
        return function (rec) {
            const selected = [];
            for (const i in items) {
                if (items[i].checked) {
                    selected.push(items[i].value);
                }
            }
            for (const i in selected) {
                if (typeof rec.genres[selected[i]] == 'undefined') {
                    return false;
                }
            }
            return true;
        }
    }

    _getValuesCount(recs) {
        var values = {};
        for (const id in recs) {
            for (const v of Object.keys(recs[id][this._key])) {
                if (!values[v]) {
                    values[v] = 0;
                }
                values[v]++;
            }
        }
        return values;
    }
}

class DbTable {
    static FILTER_FORM_NAME = '__filter_form';

    #recs;
    #cols = [];
    #conf = {};
    #statArea = null;
    #dataArea = null;

    constructor(recs) {
        this.#recs = recs;
    }

    addCol(header, content, width) {
        this.#cols.push({
            width: width,
            header: header,
            fun: typeof content == 'function' ? content : d => d[content],
        });
        return this;
    }

    addCols() {
        /* on Safari >=9.0, arguments.propertyIsEnumerable('length') returns true, so cannot use for (... in ...) */
        for (var i = 0; i < arguments.length; i++) {
            var col = arguments[i];
            this.addCol(col[0], col[1], col[2]);
        }
        return this;
    }

    addEdit(panelName = '__edit_panel__') {
        if (!canEdit()) {
            return this;
        }
        this.addCol('E', d => '<button class="bare" onclick="' + panelName + '.edit(' + d.id + ')">'
            + '<i class="fas fa-edit"></i></button>', '32px');
        return this;
    }

    addDel(panelName = '__edit_panel__') {
        if (!canEdit()) {
            return this;
        }
        this.addCol('D', d => '<button class="bare" onclick="' + panelName + '.del(' + d.id + ')">'
            + '<i class="fas fa-times"></i></button>', '32px');
        return this;
    }

    config(conf) {
        if (!conf) {
            conf = {};
        }
        if (!conf.columns) {
            conf.columns = 1;
        }
        if (!conf.filters) {
            conf.filters = [];
        }
        this.#conf = conf;
        return this;
    }

    render(eid) {
        const e = document.getElementById(eid);
        if (!e) {
            return;
        }
        const statAreaId = eid + '_stat';
        const dataAreaId = eid + '_data';
        var dataArea = document.getElementById(dataAreaId);
        if (!dataArea) {
            const p = htmlBuilder();
            const conf = this.#conf;
            for (const filter of conf.filters) {
                filter.render(p, this.#recs);
            }
            if (conf.stat) {
                p('<div id="' + statAreaId + '" class="sys"></div>');
            }
            p('<div id="' + dataAreaId + '"></div>');
            e.innerHTML = p.html();
            const self = this;
            for (const filter of conf.filters) {
                filter.attach(() => self.refresh());
            }
            dataArea = document.getElementById(dataAreaId);
        }
        this.#statArea = document.getElementById(statAreaId);
        this.#dataArea = dataArea;
        this.refresh();
        return this;
    }

    refresh() {
        const dataArea = this.#dataArea;
        if (!dataArea) {
            return;
        }
        const conf = this.#conf;
        const p = htmlBuilder();
        var recs = this.#recs;
        for (const filter of conf.filters) {
            recs = DbTable.#doFilter(recs, filter.testFun());
        }
        var grouped = null;
        if (conf.group) {
            if (!conf.group.key) {
                console.log('"key" must be set for grouping.');
                return;
            }
            grouped = DbTable.#doGroup(recs, conf.group.key);
            var groupKeys = Object.keys(grouped);
            if (typeof conf.group.sort == 'function') {
                groupKeys.sort(conf.group.sort);
            }
            if (conf.selector) {
                p('<fieldset>');
                p('<legend>' + (conf.selector.title ? conf.selector.title : 'Quick Links') + '</legend>');
                for (const key of groupKeys) {
                    p('<a href="#' + key + '">' + key + '</a> ');
                }
                p('</fieldset>');
            }
        }
        p('<table class="stylized"><colgroup>');
        const cols = this.#cols;
        if (conf.label && conf.label.left) {
            this.#pCol(p, conf.label.width);
        }
        for (var i = 0; i < conf.columns; i++) {
            for (const col of cols) {
                this.#pCol(p, col.width);
            }
        }
        p('</colgroup>');
        if (grouped) {
            for (const key of groupKeys) {
                const data = grouped[key];
                if (conf.label && conf.label.left) {
                    const numRows = (data.length - 1) / conf.columns + 1 + 1;
                    p('<tr>');
                    p('<td class="left-label" rowspan="' + numRows + '"><a id="' + key + '">' + key + '</a></td>');
                } else {
                    const numCols = cols.length * conf.columns;
                    p('<tr>');
                    p('<td class="top-label" colspan="' + numCols + '"><a id="' + key + '">' + key + '</a></td>');
                    p('</tr>');
                    p('<tr>');
                }
                this.#pHeaders(p, conf.columns);
                p('</tr>');
                this.#pRows(p, data, conf.columns, conf.sort);
            }
        } else {
            p('<tr>');
            this.#pHeaders(p, conf.columns);
            p('</tr>');
            this.#pRows(p, Object.values(recs), conf.columns, conf.sort);
        }
        p('</table>');
        dataArea.innerHTML = p.html();
        if (this.#statArea && typeof conf.stat == 'function') {
            this.#statArea.innerHTML = conf.stat(recs);
        }
    }

    #pHeaders(p, numRepeat) {
        const cols = this.#cols;
        for (var i = 0; i < numRepeat; i++) {
            for (const col of cols) {
                p('<th>' + col.header + '</th>');
            }
        }
    }

    #pCol(p, width) {
        if (width) {
            p('<col style="width:' + width + '" />');
        } else {
            p('<col />');
        }
    }

    #pRows(p, data, numCols, sort = false) {
        if (sort) {
            data.sort(sort);
        }
        const cols = this.#cols;
        var count = 0;
        var alt = true;
        for (const rec of data) {
            if (count == 0) {
                p(alt ? '<tr class="alt">' : '<tr>');
                alt = !alt;
            }
            for (const col of cols) {
                p('<td>' + col.fun(rec) + '</td>');
            }
            count++;
            if (count == numCols) {
                count = 0;
                p('</tr>');
            }
        }
        if (count != 0) {
            p('</tr>');
        }
    }

    static #doFilter(recs, filter) {
        const newRecs = {};
        for (const id in recs) {
            if (filter(recs[id])) newRecs[id] = recs[id];
        }
        return newRecs;
    }

    static #doGroup(recs, key) {
        const data = {};
        for (const id in recs) {
            const group = recs[id][key];
            if (!data[group]) data[group] = [];
            data[group].push(recs[id]);
        }
        return data;
    }
}
