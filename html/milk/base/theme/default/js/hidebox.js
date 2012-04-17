
    /**
     * HideBox control
     */
    Milk.Ctrl.HideBox = function (id) {
        this.id = id
        this.n  = null
        this.currentShow = false
        this.defaultSend = false
    }

    Milk.Ctrl.HideBox.prototype = new MilkCtrl()

    Milk.Ctrl.HideBox.prototype.init = function () {
        this.n = _$(this.id)
    }

    Milk.Ctrl.HideBox.prototype.show = function () {
        FLQ.removeClass(this.n, 'hidebox-hide')
        FLQ.addClass(this.n, 'hidebox-show')
        this.currentShow = true
        Milk.editHistory([this.id, 'show'], 1)
        this.sendSignal('show')
    }

    Milk.Ctrl.HideBox.prototype.hide = function () {
        FLQ.removeClass(this.n, 'hidebox-show')
        FLQ.addClass(this.n, 'hidebox-hide')
        this.currentShow = false
        Milk.editHistory([this.id, 'show'], 0)
        this.sendSignal('hide')
    }

    Milk.Ctrl.HideBox.prototype.toggle = function () {
        if (this.currentShow) {
            this.hide()
        } else {
            this.show()
        }
    }

