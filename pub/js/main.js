var eval = function (x) { };

function objCmp(a, b) {
    if (a > b) {
        return 1;
    } else if (a == b) {
        return 0;
    } else {
        return -1;
    }
}

function strCmp(a, b) {
    return objCmp(a, b);
}

function numCmp(a, b) {
    var aa = parseInt(a);
    var bb = parseInt(b);
    return objCmp(aa, bb);
}

function dateCmp(a, b) {
    var aa = new Date(a);
    var bb = new Date(b);
    return objCmp(aa, bb);
}

String.prototype.html = function () {
    const str = this.replace(/[<>&]/gm, s => "&#" + s.charCodeAt(0) + ";");
    return str.replace(/\r\n/gm, '<br />');
};

String.prototype.capitalize = function () {
    return this.charAt(0).toUpperCase() + this.slice(1);
};

function ajaxPost(data, onload, url = '', type = 'text/plain') {
    const r = new XMLHttpRequest();
    r.onload = () => onload(r.response);
    r.open('POST', url);
    r.setRequestHeader('Content-Type', type);
    r.send(data);
}

function htmlBuilder() {
    var html = '';
    const p = str => {
        html += str + '\n';
    }
    p.html = () => html;
    return p;
}
