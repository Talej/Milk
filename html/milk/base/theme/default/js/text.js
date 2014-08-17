
    /**
     * Text control
     */
    Milk.Ctrl.Text = function (id) {
        this.id = id
        this.n  = null
    }

    Milk.Ctrl.Text.prototype = new MilkCtrl()

    Milk.Ctrl.Text.prototype.init = function () {
        this.n = _$(this.id)
        var c = this
        FLQ.e.add(this.n, 'tap', function (e) { c.sendSignal('tap'); FLQ.e.stopEvent(e) })
    }

