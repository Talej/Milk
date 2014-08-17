
    /**
     * FileBox control
     */
    Milk.Ctrl.FileBox = function (id) {
        this.id      = id
        this.n       = null
        this.changed = false
    }

    Milk.Ctrl.FileBox.prototype = new Milk.Ctrl.Form()

    Milk.Ctrl.FileBox.prototype.init = function () {
        this.n = _$(this.id).firstChild

        var c = this
        FLQ.e.add(this.n, 'change', function () { c.changed = true })
        this.addE()
    }

    Milk.Ctrl.FileBox.prototype.hasChanged = function () {
        return this.changed
    }

    Milk.Ctrl.FileBox.prototype.addData = function (f) {
        if (this.hasChanged()) {
            f.encoding = 'multipart/form-data'
            this.n.style.display = 'none'
            f.appendChild(this.n)
        }
    };

