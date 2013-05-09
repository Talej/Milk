
    /**
     * Terminator control
     */
    Milk.Ctrl.Terminator = function (id) {
        this.id      = id
        this.reload  = true
        this.url     = null
        this.doclose = true
    }

    Milk.Ctrl.Terminator.prototype = new MilkCtrl()

    Milk.Ctrl.Terminator.prototype.init = function () {
        if (this.reload) {
            if (FLQ.isStr(this.url) && this.url.length > 0) {
                if (window.parent && window.parent.FLQ && window.parent.FLQ._ && window.parent.FLQ._['lbox']) {
                    window.parent.location = this.url
                } else if (window.opener) {
                    window.opener.location = this.url
                }
            } else {
                var l = Milk.getLauncher()
                if (l !== null && l.hasSlot('refresh')) l.refresh()
            }
        }
        if (this.doclose) this.close()
    }

    Milk.Ctrl.Terminator.prototype.close = function () {
        if (window.parent && window.parent.FLQ && window.parent.FLQ._ && window.parent.FLQ._['lbox']) {
            window.parent.FLQ.lbox.close()
        } else if (window.opener) {
            window.close()
        } else {
            window.location = '/'
        }
    }

