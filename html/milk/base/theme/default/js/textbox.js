
    /**
     * TextBox control
     */
    Milk.Ctrl.TextBox = function (id) {
        this.id = id
        this.n  = null
    }

    Milk.Ctrl.TextBox.prototype = new Milk.Ctrl.Form()

    Milk.Ctrl.TextBox.prototype.init = function () {
        this.n = _$(this.id).firstChild

        var c = this
        FLQ.e.add(this.n, 'keypress', function (e) { c.keypress(e) })
        this.addE()

        this.placeholder()
    }

    Milk.Ctrl.TextBox.prototype.keypress = function (e) {
        if (e.keyCode && e.keyCode == 13) {
            if (this.n.nodeName.toLowerCase() != 'textarea') {
                this.sendSignal('enter')
                FLQ.e.stopEvent(e)
            }
        }
    }

