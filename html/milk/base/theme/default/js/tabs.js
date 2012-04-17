
    /**
     * Tabs control
     */
    Milk.Ctrl.Tabs = function (id) {
        this.id  = id
        this.n   = null
        this.tab = null
    }

    Milk.Ctrl.Tabs.prototype = new MilkCtrl()

    Milk.Ctrl.Tabs.prototype.init = function () {
        this.n = _$(this.id)

        if (this.n.firstChild && this.n.firstChild.childNodes) {
            var i, c = this, l = this.n.firstChild.childNodes
            for (i=0; l[i]; i++) {
                if (l[i] && FLQ.hasClass(l[i], 'tablabel')) {
                    FLQ.e.add(l[i], 'tap', Function('', 'Milk.get(\''+this.id+'\').showTab('+i+')'))
                }
            }
        }

        this.showTab(this.tab)
        this.resize()
    }

    Milk.Ctrl.Tabs.prototype.showTab = function (i) {
        var p = this.id+'-'+i+'-', s = this.id+'-'+this.tab+'-'
        if (i != this.tab && !FLQ.hasClass(_$(p+'label'), 'tablabel-disabled')) {
            FLQ.removeClass(_$(s+'label'), 'tablabel-selected')
            FLQ.removeClass(_$(s+'tab'), 'tab-selected')
            FLQ.addClass(_$(p+'label'), 'tablabel-selected')
            FLQ.addClass(_$(p+'tab'), 'tab-selected')
            Milk.editHistory([this.id, 'tab'], i)
            this.tab = i
        }
    }

    Milk.Ctrl.Tabs.prototype.resize = function () {
        if (this.n.childNodes && this.n.childNodes[1] && this.n.childNodes[1].childNodes) {
            var i, mh = 0, t = this.n.childNodes[1].childNodes
            for (i=0; t[i]; i++) {
                FLQ.removeClass(t[i], 'tab-off')
                if (t[i].offsetHeight > mh) mh = t[i].offsetHeight
                FLQ.addClass(t[i], 'tab-off')
            }
            if (mh > 0) this.n.childNodes[1].style.height = mh+'px'
        }
    }

