
    /**
     * DateBox control
     */
    Milk.Ctrl.DateBox = function (id) {
        this.id       = id
        this.p        = null
        this.c        = null
        this.fmt      = '%d/%m/%Y'
        this.showtime = false
    }

    Milk.Ctrl.DateBox.prototype = new Milk.Ctrl.Form()

    Milk.Ctrl.DateBox.prototype.init = function () {
        this.p = _$(this.id)
        this.n = this.p.firstChild

        var c = this
        FLQ.e.add(this.n, 'focus', function () { c.show() })
        FLQ.e.add(this.n, 'click', function (e) { FLQ.e.stopEvent(e) })
        FLQ.e.add(this.n, 'change', function () { c.hide() })
        FLQ.e.add(this.n, 'keypress', function (e) { c.keypress(e) })
        this.addE()

        this.placeholder()
    }

    Milk.Ctrl.DateBox.prototype.keypress = function (e) {
        if (e && e.keyCode) {
            if (e.keyCode == 9) this.hide()
        }
    }

    Milk.Ctrl.DateBox.prototype.show = function (sm, sy) {
        this.hide()
        var t, b, d = 0, dow = 1, dam = 1, dim, dbm, m, y, sd, dt,
        a = true,
        v = new Date()

        if (arguments.length >= 2) {
            v.setDate(1)
            v.setMonth(sm)
            v.setYear(sy)
            a = false
        } else if (tmp = this.getValue()) {
            v.strptime(tmp, this.fmt)
        }
        sd = this.getFDIM(v)
        y = v.getFullYear()
        m = v.getMonth()
        dim = v.dim[m]
        dbm = v.dim[(m == 0 ? 11 : m-1)]-(sd-2)

        this.c = document.createElement('div')
        FLQ.addClass(this.c, 'datebox-cal')
        this.c.appendChild(t = document.createElement('table'))
        t.appendChild(b = document.createElement('tbody'))

        var c = this
        FLQ.e.add(document.body, 'click', function () { setTimeout('Milk.get(\''+c.id+'\').hide()', 100) })

        // calendar nav & month label
        b.appendChild(tr = document.createElement('tr'))
        tr.appendChild(td = document.createElement('td'))
        FLQ.addClass(tr, 'datebox-title');
        td.appendChild(document.createTextNode('«'))
        FLQ.addClass(td, 'datebox-prev');
        FLQ.e.add(td, 'click', function (e) { c.show((m == 0 ? 11 : m-1), (m == 0 ? y-1 : y)); FLQ.e.stopEvent(e) })
        tr.appendChild(td = document.createElement('td'))
        td.appendChild(document.createTextNode(v.mths[m]+' '+y))
        td.colSpan = 5;
        tr.appendChild(td = document.createElement('td'))
        td.appendChild(document.createTextNode('»'))
        FLQ.addClass(td, 'datebox-next');
        FLQ.e.add(td, 'click', function (e) { c.show((m == 11 ? 0 : m+1), (m == 11 ? y+1 : y));  FLQ.e.stopEvent(e) })

        b.appendChild(tr = document.createElement('tr'))
        FLQ.addClass(tr, 'datebox-dow')
        for (var i=1; i < 7; i++) {
            tr.appendChild(td = document.createElement('td'))
            td.appendChild(document.createTextNode(v.wd[i].substr(0, 1)))
            if (i == 6) { i = -1 } else if (i == 0) { break }
        }

        b.appendChild(tr = document.createElement('tr'))
        dt = new Date(y, m, 1, v.getHours(), v.getMinutes(), v.getSeconds())
        for (var i=1; i <= 42; i++) {
            cl = ''
            if (dow > 7) {
                if (d >= dim) break;
                b.appendChild(tr = document.createElement('tr'))
                dow = 1;
            }
            dow++;
            if (i >= sd && d < dim) {
                ++d
                dt.setDate(d)

                if (v.getDate() == d && m == v.getMonth()) cl+= ' datebox-today'

                cl+= ' datebox-day';
                tr.appendChild(td = document.createElement('td'))
                td.appendChild(document.createTextNode(d));
                FLQ.addClass(td, cl);
                FLQ.e.add(td, 'click', new Function('', 'with (ctr = Milk.get(\''+c.id+'\')) { setvalue({\'value\': getCalValue(\''+dt.strftime(c.fmt)+'\')}) }'))
            } else {
                tr.appendChild(td = document.createElement('td'))
                FLQ.addClass(td, 'datebox-nonday')
                td.appendChild(document.createTextNode(d >= dim ? (dam++) : (dbm++)))
            }
        }

        if (this.showtime) {
            this.c.appendChild(t = document.createElement('div'))
            FLQ.addClass(t, 'datebox-time')
            t.appendChild(s = document.createElement('strong'))
            s.appendChild(document.createTextNode('Time: '))
            t.appendChild(h = document.createElement('select'))
            o = 0
            for (var i=0; i <= 23; i++) {
                h.options[o++] = new Option((i < 10 ? '0' : '')+i, i, (v.getHours() == i ? true : false))
            }
            FLQ.e.add(h, 'click', function (e) { FLQ.e.stopEvent(e); })
            t.appendChild(mi = document.createElement('select'))
            o = 0
            for (var i=0; i < 60; i++) {
                mi.options[o++] = new Option((i < 10 ? '0' : '')+i, i, (v.getMinutes() == i ? true : false))
            }
            FLQ.e.add(mi, 'click', function (e) { FLQ.e.stopEvent(e); })
        }

        if (a) this.c.style.height = '0px'
        this.p.appendChild(this.c)
        if (a) this.iId = setInterval('Milk.get(\''+c.id+'\').showAnim()', 1)
    }

    Milk.Ctrl.DateBox.prototype.getCalValue = function (d) {
        if (this.showtime) {
            v = new Date()
            v.strptime(d, this.fmt)
            if (s = this.c.getElementsByTagName('select')) {
                v.setHours(s[0].options[s[0].selectedIndex].value, s[1].options[s[1].selectedIndex].value)
            }

            return v.strftime(this.fmt)
        }

        return d
    }

    Milk.Ctrl.DateBox.prototype.showAnim = function () {
        if (this.c && this.c.offsetHeight < 200) {
            this.c.style.height = (this.c.offsetHeight+5)+'px'
        } else {
            clearInterval(this.iId)
        }
    }

    Milk.Ctrl.DateBox.prototype.isLeapYear = function (y) {
        y = parseInt(year)
        if (y%4 == 0) {
            if (y%100 != 0) {
                return true
            } else if (y%400 == 0) {
                return true
            }
        }

        return false
    }

    Milk.Ctrl.DateBox.prototype.getFDIM = function (v) {
        d = new Date(v)
        d.setDate(1)
        var sd = d.getDay()
        return (sd == 0 ? 7 : sd)
    }

    Milk.Ctrl.DateBox.prototype.hide = function () {
        if (this.c && this.c.parentNode) {
            this.c.parentNode.removeChild(this.c)
        }
        var c = this
        FLQ.e.removeListener(document.body, 'click', function () { setTimeout('Milk.get(\''+c.id+'\').hide()', 100) })
    }

