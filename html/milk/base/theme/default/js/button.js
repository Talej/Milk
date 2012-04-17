
    /**
     * Button control
     */
    Milk.Ctrl.Button = function (id) {
        this.id        = id
        this.n         = null
        this.dodisable = false
        this.disabled  = false
    }

    Milk.Ctrl.Button.prototype = new MilkCtrl()

    Milk.Ctrl.Button.prototype.init = function () {
        this.n = _$(this.id)
        var c = this
        FLQ.e.add(this.n, 'tap', function (e) { if (!c.disabled) { if (c.dodisable) c.disable({disable:true}); c.sendSignal('click'); } FLQ.e.stopEvent(e) })
    }

    Milk.Ctrl.Button.prototype.disable = function (args) {
        var d = (FLQ.isSet(typeof args['disable']) ? args['disable'] : true);
        if (d) {
            this.disabled = true;
            FLQ.addClass(this.n, 'button-disabled');
        } else {
            this.disabled = false;
            FLQ.removeClass(this.n, 'button-disabled');
        }
    }

    Milk.Ctrl.Button.prototype.slotdone = function () {
        this.disable({disable:false})
    }

