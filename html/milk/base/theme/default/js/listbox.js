
    /**
     * ListBox control
     */
    Milk.Ctrl.ListBox = function (id) {
        this.id        = id
        this.min       = null
        this.max       = 1
        this.filterKey = null
        this.filters   = null
        this.origOpts  = null
    }

    Milk.Ctrl.ListBox.prototype = new Milk.Ctrl.Form()

    Milk.Ctrl.ListBox.prototype.init = function () {
        this.n = _$(this.id).firstChild
        this.addE()

        var i, o, c = this
        this.origOpts = []
        for (i=0; this.n.options[i]; i++) {
            o = new Option(this.n.options[i].text, this.n.options[i].value, this.n.options[i].selected)
            o.selected = this.n.options[i].selected
            FLQ.addClass(o, this.n.options[i].className)
            this.origOpts.push(o)
        }

        var f = function () {
            c.sendSignal('change', {'value':c.getValue()})
            c.filterset()
        }
        FLQ.e.add(this.n, 'change', f)
        setTimeout('Milk.get(\''+this.id+'\').filterset()', 0)
    }

    Milk.Ctrl.ListBox.prototype.filterset = function () {
        if (this.filterKey) {
            var args = {'send' : false, 'filters' : {}}
            args['filters'][this.filterKey] = this.getValue()
            this.sendSignal('filterset', args)
        }
    }

    Milk.Ctrl.ListBox.prototype.getValue = function () {
        var v, i
        if (this.max > 1 || this.max == null) {
            v = []
            for (i=0; this.n.options[i]; i++) {
                if (this.n.options[i].selected) {
                    v.push(this.n.options[i].value)
                }
            }

            return v
        } else {
            if (this.n.selectedIndex != -1 && this.n.options[this.n.selectedIndex]) {
                v = this.n.options[this.n.selectedIndex].value
                return (v.length == 0 ? null : v)
            } else {
                return null
            }
        }
    }

    Milk.Ctrl.ListBox.prototype.filter = function (args) {
        if (FLQ.isObj(this.filters) && FLQ.isSet(typeof args['filters']) && FLQ.isObj(args['filters'])) {
            var i, o, nf, r, c, f, opts = {}
            for (i in args['filters']) {
                if (FLQ.isSet(typeof this.filters[i])) {
                    for (o in this.filters[i]) {
                        nf = true
                        if (FLQ.isArr(this.filters[i][o])) {
                            for (r=0; this.filters[i][o][r]; r++) {
                                if (this.filters[i][o][r] == args['filters'][i]) {
                                    opts[o] = true
                                    nf = false
                                } else if (nf) {
                                    delete opts[o]
                                }
                            }
                        } else {
                            if (this.filters[i][o] == args['filters'][i]) {
                                opts[o] = true
                            } else if (FLQ.isSet(typeof opts[o])) {
                                delete opts[o]
                            }
                        }
                    }
                }
            }

            f = this.origOpts.slice()
            this.n.options.length = 0
            for (c=0,i=0; f[i]; i++) {
                if (FLQ.isSet(typeof opts[f[i].value]) || f[i].value == '') {
                    this.n.options[c++] = new Option(f[i].text, f[i].value, f[i].selected)
                    this.n.options[c-1].selected = f[i].selected
                    FLQ.addClass(this.n.options[c-1], f[i].className)
                }
            }
        }
    }

