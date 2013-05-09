
    /**
     * PasswordBox control
     */
    Milk.Ctrl.PasswordBox = function (id) {
        this.id = id
    }

    Milk.Ctrl.PasswordBox.prototype = new Milk.Ctrl.TextBox()

    Milk.Ctrl.PasswordBox.prototype.init = function () {
        this.n = _$(this.id).firstChild

        var c = this
        FLQ.e.add(this.n, 'keypress', function (e) { c.keypress(e) })
        this.addE()
    }

