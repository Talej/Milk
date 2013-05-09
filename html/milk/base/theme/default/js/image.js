
    /**
     * Image control
     */
    Milk.Ctrl.Image = function (id) {
        this.id = id
        this.n  = null
    }

    Milk.Ctrl.Image.prototype = new MilkCtrl()

    Milk.Ctrl.Image.prototype.init = function () {
        this.n = _$(this.id)

        var c = this
        FLQ.e.add(this.n, 'tap', function () { c.sendSignal('tap') })
        FLQ.e.add(this.n, 'mouseover', function () { c.sendSignal('over') })
        FLQ.e.add(this.n, 'mouseout', function () { c.sendSignal('out') })
    }

    Milk.Ctrl.Image.prototype.setsrc = function (args) {
        var v
        if (v = Milk.getArg(args, 'value')) {
            this.n.firstChild.src = v
        }
    }

