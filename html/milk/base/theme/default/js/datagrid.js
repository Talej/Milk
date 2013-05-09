
    /**
     * DataGrid control
     */
    Milk.Ctrl.DataGrid = function (id) {
        this.id              = id
        this.defaultRequire  = true
        this.n               = null
        this.b               = null
        this.f               = []
        this.perpage         = null
        this.offset          = null
        this.totalrows       = null
        this.connected       = false
        this.sortCol         = 0
        this.sortDesc        = false
    }

    Milk.Ctrl.DataGrid.prototype = new MilkCtrl()

    Milk.Ctrl.DataGrid.prototype.init = function () {
        this.n = _$(this.id)
        this.b = this.n.tBodies[0]

        var i, c = this, tr = this.n.getElementsByTagName('TR'), th = this.n.getElementsByTagName('TH')
        if (this.connected) {
            for (i=1; tr[i]; i++) {
                FLQ.e.add(tr[i], 'mouseover', function () { FLQ.addClass(this, 'datagrid-hover'); c.sendSignal('hover') })
                FLQ.e.add(tr[i], 'mouseout', function () { FLQ.removeClass(this, 'datagrid-hover') })
                FLQ.e.add(tr[i], 'click', function (e) { c.focus(this.rowIndex, e) })
                FLQ.e.add(tr[i], 'dblclick', function (e) { c.select(this.rowIndex, e) })
            }
        }
        for (i=0; th[i]; i++) {
            FLQ.e.add(th[i], 'click', Function('', 'Milk.get(\''+this.id+'\').sort('+i+')'))
        }
    }

    Milk.Ctrl.DataGrid.prototype.getValue = function () {
        var vs = [], r, v, a, i
        if (FLQ.isArr(this.f)) {
            for (i=0; this.f[i]; i++) {
                r = this.f[i]
                v = {}
                if (r !== null && (a = this.b.rows[r].getAttribute('actarg'))) {
                    v = FLQ.URL.parseArgs(a)
                }

                vs.push(v)
            }
        }

        if (vs.length == 1) {
            return vs[0]
        } else {
            v = {}
            if (vs.length) v['value'] = vs
            for (i=0; vs[i]; i++) v[i] = vs[i]
            return v
        }    
    }

    Milk.Ctrl.DataGrid.prototype.focus = function (r, e) {
        if (r < 0) return false
        if (e.ctrlKey || e.metaKey) {
            var i
            if ((i = this.f.search(r)) != -1) { // it's selected so deselect
                FLQ.removeClass(this.b.rows[r], 'datagrid-focus')
                this.f.splice(i, 1)
            } else {
                FLQ.addClass(this.b.rows[r], 'datagrid-focus')
                this.f.push(r)
                this.sendSignal('focus')
            }
        } else if (this.f.search(r) == -1 || this.f.length > 1) {
            if (this.b && this.b.rows && this.b.rows[r]) {
                if (this.f.length > 0) {
                    for (var i=0; this.f[i]; i++) {
                        FLQ.removeClass(this.b.rows[this.f[i]], 'datagrid-focus')
                    }
                }
                FLQ.addClass(this.b.rows[r], 'datagrid-focus')
                this.f = [r]
                this.sendSignal('focus')
            }
        }
    }

    Milk.Ctrl.DataGrid.prototype.select = function (r, e) {
        if (this.f.search(r) == -1) this.focus(r, e)
        this.sendSignal('select')
    }

    Milk.Ctrl.DataGrid.prototype.first = function () {
        Milk.editHistory([this.id, 'offset'], 0)
        new Milk.Conn(this, 'first', Milk.SLOT_SAMEWIN, 'refresh').exec({'send':true})
        this.offset = 0
    }

    Milk.Ctrl.DataGrid.prototype.prev = function () {
        var os = Math.max(0, (this.offset-this.perpage))
        Milk.editHistory([this.id, 'offset'], os)
        new Milk.Conn(this, 'previous', Milk.SLOT_SAMEWIN, 'refresh').exec({'send':true})
        this.offset = os
    }

    Milk.Ctrl.DataGrid.prototype.next = function () {
        var os = Math.min(this.totalrows-1, (this.offset+this.perpage))
        Milk.editHistory([this.id, 'offset'], os)
        new Milk.Conn(this, 'next', Milk.SLOT_SAMEWIN, 'refresh').exec({'send':true})
        this.offset = os
    }

    Milk.Ctrl.DataGrid.prototype.last = function () {
        var os = this.totalrows-1
        Milk.editHistory([this.id, 'offset'], os)
        new Milk.Conn(this, 'last', Milk.SLOT_SAMEWIN, 'refresh').exec({'send':true})
        this.offset = os
    }

    Milk.Ctrl.DataGrid.prototype.sort = function (c) {
        var d = (c == this.sortCol && !this.sortDesc ? 1 : 0)
        Milk.editHistory([this.id, 'sortCol'], c)
        Milk.editHistory([this.id, 'sortDesc'], d)
        new Milk.Conn(this, 'sort', Milk.SLOT_SAMEWIN, 'refresh').exec({'send':true})
    }

    Milk.Ctrl.DataGrid.prototype.csv = function () {
        Milk.editHistory([this.id, 'csv'], 1)
        new Milk.Conn(this, 'sort', Milk.SLOT_SAMEWIN, 'refresh').exec({'send':true})
    }

