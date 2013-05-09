
    /**
     * ChooseBox control
     */
    Milk.Ctrl.ChooseBox = function (id) {
        this.id = id
        this.n = null
    }

    Milk.Ctrl.ChooseBox.prototype = new Milk.Ctrl.Form()

    Milk.Ctrl.ChooseBox.prototype.init = function () {
        this.n = _$(this.id).firstChild

        var c = this
        if (this.n.nextSibling) FLQ.e.add(this.n.nextSibling, 'tap', function () { c.sendSignal('choose', {'send':false}) })
    }

    Milk.Ctrl.ChooseBox.prototype.setvalue = function (args) {
        var v
        if (v = Milk.getArg(args, 'value')) {
            this.reqValue = v
            this.n.value = v[1]
            this.sendSignal('change', {'value':this.getValue()})
        }
    }

    Milk.Ctrl.ChooseBox.prototype.getValue = function () {
        return this.reqValue
    }

