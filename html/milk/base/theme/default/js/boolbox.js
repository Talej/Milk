
    /**
     * Boolean control
     */
    Milk.Ctrl.BoolBox = function (id) {
        this.id = id
        this.n = null
    }

    Milk.Ctrl.BoolBox.prototype = new Milk.Ctrl.Form()

    Milk.Ctrl.BoolBox.prototype.init = function () {
        this.n = _$(this.id).firstChild

        var c = this
        FLQ.e.add(this.n, 'change', function () { c.sendSignal('change', {'value':c.getValue()}); c.sendSignal(c.n.checked ? 'on' : 'off') })
    }

    Milk.Ctrl.BoolBox.prototype.getValue = function () {
        return (this.n.checked ? 1 : 0)
    }

    Milk.Ctrl.BoolBox.prototype.setvalue = function (args) {
        var v = (Milk.getArg(args, 'value') ? true : false)
        this.n.checked = v
        this.value = v
        
        this.sendSignal(this.n.checked ? 'on' : 'off')
    }

    Milk.Ctrl.BoolBox.prototype.toggle = function () {
        this.setvalue({'value':(this.getValue() ? false : true)})
        this.sendSignal('slotdone');
    }
    
    Milk.Ctrl.BoolBox.prototype.on = function () {
        this.setvalue({'value':true})
    }

    Milk.Ctrl.BoolBox.prototype.off = function () {
        this.setvalue({'value':false})
    };

