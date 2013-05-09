
    /**
     * DateTimeBox control
     */
    Milk.Ctrl.DateTimeBox = function (id) {
        this.id       = id
        this.p        = null
        this.fmt      = '%d/%m/%Y %H:%M'
        this.showtime = true
    }

    Milk.Ctrl.DateTimeBox.prototype = new Milk.Ctrl.DateBox()

    Milk.Ctrl.DateTimeBox.prototype.init = function () {
        this.p = $(this.id)
        this.n = this.p.firstChild

        var c = this
        FLQ.e.add(this.n, 'focus', function () { c.show() })
        FLQ.e.add(this.n, 'click', function (e) { FLQ.e.stopEvent(e) })
        FLQ.e.add(this.n, 'change', function () { c.hide() })
        FLQ.e.add(this.n, 'keypress', function (e) { c.keypress(e) })
        this.addE()

        this.placeholder()
    }

