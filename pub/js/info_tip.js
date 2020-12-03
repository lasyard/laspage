class InfoTip {
    #infoDiv;
    #setInfo;
    #callback;
    #hook;

    constructor(infoDiv, closeButton, setInfo) {
        const hook = '__info_tip_show__';
        const self = this;
        window[hook] = (e, id) => self.show(e, id);
        this.#hook = hook;
        document.body.onclick = closeButton.onclick = (e) => {
            infoDiv.style.display = 'none';
        };
        infoDiv.onclick = e => e.stopPropagation();
        this.#infoDiv = infoDiv;
        this.#setInfo = setInfo;
    }

    setCallback(callback) {
        this.#callback = callback;
    }

    show(e, context) {
        const infoDiv = this.#infoDiv;
        this.#setInfo(this.#callback(context));
        const doc = document.documentElement;
        const body = document.body;
        e = e || window.event;
        if (e.pageX == null) {
            e.pageX = e.clientX + (doc && doc.scrollLeft || body && body.scrollLeft || 0);
            e.pageY = e.clientY + (doc && doc.scrollTop || body && body.scrollTop || 0);
        }
        infoDiv.style.left = e.pageX + 'px';
        infoDiv.style.top = e.pageY + 'px';
        infoDiv.style.display = 'block';
        var height = window.innerHeight || doc.clientHeight;
        var width = window.innerWidth || doc.clientWidth;
        if (e.clientX + infoDiv.offsetWidth > width) {
            infoDiv.style.left = e.pageX - infoDiv.offsetWidth + 'px';
        }
        if (e.clientY + infoDiv.offsetHeight > height) {
            infoDiv.style.top = e.pageY - infoDiv.offsetHeight + 'px';
        }
        e.stopPropagation();
    }

    link(id, title) {
        return '<a href="javascript:void(0)" onclick="' + this.#hook + '(event, \'' + id + '\')">' + title + '</a>';
    }
}
