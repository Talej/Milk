
    if (typeof FLQ == 'undefined') var FLQ = {}

    if (typeof FLQ._ == 'undefined') FLQ._ = {}

    FLQ.isSet = function (v) {
        return (v != 'undefined' ? true : false)
    }

    FLQ.isNull = function (v) {
        return (v === null ? true : false)
    }

    FLQ.isFunc = function (v) {
        if (typeof v == 'function' || (FLQ.isObj(v) && String(v.constructor) == String(Function))) {
            return true
        } else if (FLQ.isObj(v)) {
            try {
                return (String(v).search(/^\s*function [a-zA-Z]+\(\) \{\s+\[native code\]\s+\}\s*$/) != -1 ? true : false)
            } catch (z) { }
        }

        return false
    }

    FLQ.isObj = function (v) {
        return (!FLQ.isNull(v) && typeof v == 'object' ? true : false)
    }

    FLQ.isArr = function (v) {
        return (FLQ.isObj(v) && String(v.constructor) == String(Array) ? true : false)
    }

    FLQ.isStr = function (v) {
        return (typeof v == 'string' ? true : false)
    }

    FLQ.isInt = function (v) {
        return (typeof v == 'number' && Math.floor(v) == v ? true : false)
    }

    FLQ.isBool = function (v) {
        return (typeof v == 'boolean' || v === true || v === false ? true : false)
    }

    FLQ.isScalar = function (v) {
        return (FLQ.isStr(v) || FLQ.isInt(v) || FLQ.isFloat(v) || FLQ.isBool(v) ? true : false)
    }

    FLQ.isRegex = function (v) {
        return (v && FLQ.isFunc(v.test)) || (FLQ.isObj(v) && String(v.constructor) == String(RegExp))
    }

    FLQ.ifNull = function (v) {
        var a = arguments
        for (i=0; FLQ.isSet(typeof a[i]); i++) {
            if (!FLQ.isNull(a[i])) return a[i]
        }

        return null
    }

    FLQ.ifNaN = function (v) {
        var a = arguments
        for (i=0; FLQ.isSet(a[i]); i++) {
            if (!isNaN(a[i])) return a[i]
        }

        return NaN;
    }

    if (!FLQ.isFunc(Array.prototype.search)) {
        if (FLQ.isFunc(Array.prototype.indexOf)) {
            Array.prototype.search = Array.prototype.indexOf
        } else {
            Array.prototype.search = function (v) {
                for (var i=0; this[i]; i++) {
                    if (this[i] === v) return i
                }

                return -1
            }
        }
    }

    FLQ.toHex = function (s) {
        var r = '', h, i
        for (i = 0; i < s.length; i++) {
            h = s.charCodeAt(i).toString(16).toUpperCase()
            r += (h.length == 1 ? '0' : '')+h
        }

        return r
    }

    FLQ.fromHex = function (s) {
        var r = '', i
        for (i=0; i < s.length; i+=2) {
            r += String.fromCharCode(parseInt(s.substr(i,2), 16))
        }

        return r
    }

    FLQ.nl2br = function (s) {
        return String(s).replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br />$2')
    }

    FLQ.addClass = function (n, c) {
        var m = n.className.split(/ +/), f = false, s = ''
        for (i in m) {
            if (!FLQ.isFunc(m[i])) {
                s+= (s.length > 0 ? ' ' : '')+m[i]
                if (m[i] == c) f = true
            }
        }
        if (!f) {
            s+= (s.length > 0 ? ' ' : '')+c
        }
        n.className = s
    }

    FLQ.removeClass = function (n, c) {
        var m = n.className.split(/ +/), s = ''
        for (i in m) {
            if (m[i] != c && !FLQ.isFunc(m[i])) {
                s+= (s.length > 0 ? ' ' : '')+m[i]
            }
        }
        n.className = s
    }

    FLQ.hasClass = function (n, c) {
        if (n.className) {
            var m = n.className.split(/ +/)
            for (i in m) {
                if (m[i] == c) return true
            }
        }

        return false
    }

    FLQ.getAvailHeight = function(n) {
        var u = 0, t = n.lastChild, s
        while (t) {
            s = FLQ.getStyle(t)
            if (s.position != 'absolute') {
                u = FLQ.ifNaN(parseInt(t.offsetTop),0)+FLQ.ifNaN(parseInt(s.marginTop),0)+FLQ.ifNaN(parseInt(s.marginBottom),0)
                break
            }
            t = t.previousSibling
        }
        s = FLQ.getStyle(n)
        u+= FLQ.ifNaN(parseInt(s.paddingBottom),0)+FLQ.ifNaN(parseInt(s.paddingTop),0)+FLQ.ifNaN(parseInt(s.borderTopWidth),0)+FLQ.ifNaN(parseInt(s.borderBottomWidth),0)
        return n.offsetHeight-u
    }

    FLQ.getStyle = function (n) {
        var s = {}
        if (window.getComputedStyle) {
            s = window.getComputedStyle(n, '')
        } else if (n.currentStyle) {
            s = n.currentStyle
        }

        return s
    }

    FLQ.setNodeWidth = function (n, px) {
        var s, i, k
        if (px > 0 && (s = FLQ.getStyle(n))) {
            k = ['paddingLeft', 'paddingRight', 'borderLeftWidth', 'borderRightWidth']
            for (i=0; k[i]; i++) {
                px-= FLQ.ifNaN(parseInt(s[k[i]]),0)
            }
            if (px >= 0) n.style.width = px+'px'
        }
    }

    FLQ.setNodeHeight = function (n, px) {
        var s, i, k
        if (px > 0 && (s = FLQ.getStyle(n))) {
            k = ['paddingTop', 'paddingBottom', 'borderBottomWidth', 'borderTopWidth']
            for (i=0; k[i]; i++) {
                px-= FLQ.ifNaN(parseInt(s[k[i]]),0)
            }
            if (px >= 0) n.style.height = px+'px'
        }
    };

    FLQ.popup = function (url, props) {
        if (arguments.length < 2) props = {}

        props['scrollbars'] = '1';
        var s = '', n = ''
        if (FLQ.isSet(typeof props['name'])) n = props['name']
        for (i in props) {
            s+= (s != '' ? ',' : '') + i+'='+props[i]
        }
// TODO: Need to add support for "launcher" property
        window.open(url, n, s)
    }

    FLQ.lbox = function () {
        return FLQ.lbox.open.apply(FLQ.popup, arguments)
    }

    FLQ.lbox.minWidth = 200
    FLQ.lbox.minHeight = 100

    FLQ.lbox.open = function (href) {
        if (FLQ.isObj(FLQ._['lbox'])) FLQ.lbox.close()

        var a = arguments
        var o = (a.length > 1 && FLQ.isObj(a[a.length-1]) ? a[a.length-1] : {})
        if (!FLQ.isSet(typeof o['width']) && a.length > 2) o['width'] = a[1]
        if (!FLQ.isSet(typeof o['height']) && a.length > 3) o['height'] = a[2]
        var d = {'width':600,'height':400,'toolbars':'no','scrollbars':'yes','resizable':true}
        for (var i in d) { if (!FLQ.isSet(typeof o[i])) o[i] = d[i] }
        if (FLQ.isSet(typeof o['name'])) {
            var n = o['name']
            delete o['name']
        } else {
            var n = href.replace(/[^a-zA-Z0-9_]/g, '')
        }

        var d = document.createElement('div')
        FLQ.addClass(d, 'lboxwindow')

        d.appendChild(b = document.createElement('div'))
        FLQ.addClass(b, 'background')

        d.appendChild(i = document.createElement('div'))
        FLQ.addClass(i, 'lboxwindow-inner')
        i.style.width = o['width']+'px'
        i.style.height = o['height']+'px'
        FLQ.lbox.setCenterPos(i)

        i.innerHTML = '<iframe frameborder="0" name="'+n+'" src="'+href+'"></iframe>'

        // Re-center the lbox when the window is resized
        FLQ.event.add(window, 'resize', function () { FLQ.lbox.setCenterPos(i) })

        // Close button
        i.appendChild(c = document.createElement('div'))
        FLQ.addClass(c, 'close')
        FLQ.event.add(c, 'click', FLQ.lbox.close)

        // Add Esc shortcut to close lbox - char below is the keyboard escape key (keyCode 27)
        FLQ.shortcut.add('Esc', FLQ.lbox.close)

        // Resize button
        if (o['resizable']) {
            i.appendChild(r = document.createElement('div'))
            FLQ.addClass(r, 'resize')
            FLQ.event.add(r, 'mousedown', FLQ.lbox.startResize)
        }

        if (FLQ.isSet(typeof o['args'])) {
            if (!FLQ.isObj(FLQ._['lboxargs'])) FLQ._['lboxargs'] = {}
            FLQ._['lboxargs'] = o['args']
            delete o['args']
        }

        // Move event
        i.appendChild(m = document.createElement('div'))
        FLQ.addClass(m, 'move')
        FLQ.event.add(m, 'mousedown', FLQ.lbox.startMove)

        document.body.appendChild(d)
        FLQ._['lbox'] = d

        return i.firstChild
    }

    FLQ.lbox.close = function (reload) {
        if (!FLQ.isBool(reload)) reload = false
        if (FLQ.isObj(FLQ._['lbox'])) {
            if (reload) window.location.reload()
            FLQ._['lbox'].parentNode.removeChild(FLQ._['lbox'])
            FLQ._['lbox'] = null
            FLQ._['lboxargs'] = null
        }
        FLQ.shortcut.remove('Esc')
    }

    FLQ.lbox.setCenterPos = function (n) {
        var wWidth = (FLQ.isSet(typeof window.innerWidth) ? window.innerWidth : document.documentElement.clientWidth)
        var wHeight = (FLQ.isSet(typeof window.innerHeight) ? window.innerHeight : document.documentElement.clientHeight)
        n.style.left = Math.max((wWidth-parseInt(n.style.width))/2, 0)+'px'
        n.style.top = Math.max(((wHeight-parseInt(n.style.height))/2)-20, 0)+'px'
    }

    FLQ.lbox.getLauncher = function () {
        return window.top
    }

    FLQ.lbox.getArgs = function () {
        if (window.parent) {
            var o = window.parent
            if (
                FLQ.isSet(typeof o.FLQ) &&
                FLQ.isSet(typeof o.FLQ._) &&
                FLQ.isSet(typeof o.FLQ._['lboxargs'])
            ) {
                return o.FLQ._['lboxargs']
            }
        }

        return null
    }

    FLQ.lbox.autoHeight = function () {
        if (FLQ._['lbox']) {
            var i = FLQ._['lbox'].childNodes[1]
            var d = i.getElementsByTagName('iframe')[0].contentWindow.document
            // TODO: Work out where the 4px is coming from
            if (d.body) {
                css = FLQ.getStyle(i)
                i.style.height = (d.body.scrollHeight+FLQ.ifNaN(parseInt(css.paddingTop),0)+FLQ.ifNaN(parseInt(css.paddingBottom),0))+'px'
//                 if (d.height) {
//                     i.style.height = Math.min(Math.max(d.height, d.body.offsetHeight, d.body.clientHeight)+6, window.top.innerHeight-60)+'px'
//                 } else {
//                     i.style.height = Math.min(Math.max(d.body.scrollHeight, d.body.offsetHeight, d.body.clientHeight)+6, window.top.document.documentElement.clientHeight-60)+'px'
//                 }
            }
            FLQ.lbox.setCenterPos(i)
        }
    }

    FLQ.lbox.startResize = function (e) {
        if (FLQ._['lbox']) {
            if (FLQ.lbox.moving) FLQ.lbox.endMove()
            var iframe = FLQ._['lbox'].getElementsByTagName('iframe')[0]
            FLQ.event.add(document.body, 'mouseup', FLQ.lbox.endResize)
            FLQ.event.add(document.body, 'mousemove', FLQ.lbox.resize)
            FLQ.event.add(iframe.contentWindow.document.body, 'mouseup', FLQ.lbox.endResize)
            FLQ.event.add(iframe.contentWindow.document.body, 'mousemove', FLQ.lbox.resize)
            FLQ.lbox.X = e.screenX
            FLQ.lbox.Y = e.screenY
            FLQ.lbox.resizing = true
            FLQ.event.stopEvent(e)
        }
    }

    FLQ.lbox.endResize = function () {
        var iframe = FLQ._['lbox'].getElementsByTagName('iframe')[0]
        FLQ.event.removeListener(document.body, 'mousemove', FLQ.lbox.resize)
        FLQ.event.removeListener(document.body, 'mouseup', FLQ.lbox.endResize)
        FLQ.event.removeListener(iframe.contentWindow.document.body, 'mouseup', FLQ.lbox.endResize)
        FLQ.event.removeListener(iframe.contentWindow.document.body, 'mousemove', FLQ.lbox.resize)
        FLQ.lbox.resizing = false
    }

    FLQ.lbox.resize = function (e) {
        if (FLQ._['lbox']) {
            var m = FLQ._['lbox'].getElementsByTagName('iframe')[0].parentNode
            var end = false

            if (m) {
                var w = e.screenX-FLQ.lbox.X
                var h = e.screenY-FLQ.lbox.Y
                if (w > 3 || w < 3) {
                    var mw = parseInt(m.style.width)
                    var neww = (mw != NaN ? mw : m.offsetWidth)+w
                    if (neww >= FLQ.lbox.minWidth) {
                        m.style.width = neww+'px'
                        FLQ.lbox.X = e.screenX
                    } else {
                        end = true
                    }
                }
                if (h > 3 || h < 3) {
                    var mh = parseInt(m.style.height)
                    var newh = (mh != NaN ? mh : m.offsteHeight)+h
                    if (newh >= FLQ.lbox.minHeight) {
                        m.style.height = newh+'px'
                        FLQ.lbox.Y = e.screenY
                    } else {
                        end = true
                    }
                }
            }

            if (end) FLQ.lbox.endResize()
        }
    }

    FLQ.lbox.startMove = function (e) {
        if (FLQ._['lbox']) {
            if (FLQ.lbox.resizing) FLQ.lbox.endResize()
            var iframe = FLQ._['lbox'].getElementsByTagName('iframe')[0]
            FLQ.event.add(document.body, 'mouseup', FLQ.lbox.endMove)
            FLQ.event.add(document.body, 'mousemove', FLQ.lbox.move)
            FLQ.event.add(iframe.contentWindow.document.body, 'mouseup', FLQ.lbox.endMove)
            FLQ.event.add(iframe.contentWindow.document.body, 'mousemove', FLQ.lbox.move)
            FLQ.lbox.X = e.screenX
            FLQ.lbox.Y = e.screenY
            FLQ.lbox.moving = true

            var m = iframe.parentNode
            if (m) {
                FLQ.addClass(m, 'moving')
            }
        }
    }

    FLQ.lbox.endMove = function () {
        var iframe = FLQ._['lbox'].getElementsByTagName('iframe')[0]
        FLQ.event.removeListener(document.body, 'mousemove', FLQ.lbox.move)
        FLQ.event.removeListener(document.body, 'mouseup', FLQ.lbox.endMove)
        FLQ.event.removeListener(iframe.contentWindow.document.body, 'mouseup', FLQ.lbox.endMove)
        FLQ.event.removeListener(iframe.contentWindow.document.body, 'mousemove', FLQ.lbox.move)
        FLQ.lbox.moving = false

        var m = FLQ._['lbox'].getElementsByTagName('iframe')[0].parentNode
        if (m) {
            FLQ.removeClass(m, 'moving')
        }
    }

    FLQ.lbox.move = function (e) {
        if (FLQ._['lbox']) {
            var m = FLQ._['lbox'].getElementsByTagName('iframe')[0].parentNode

            if (m) {
                var l = e.screenX-FLQ.lbox.X
                var t = e.screenY-FLQ.lbox.Y
                if (l > 3 || l < 3) {
                    m.style.left = (parseInt(m.style.left)+l)+'px'
                    FLQ.lbox.X = e.screenX
                }
                if (t > 3 || t < 3) {
                    m.style.top = (parseInt(m.style.top)+t)+'px'
                    FLQ.lbox.Y = e.screenY
                }
            }
        }
    }

    Number.prototype.pad = function (n, p) {
        var s = '' + this
        p = p || '0'
        while (s.length < n) s = p + s
        return s
    }

    // Date object extensions
    Date.prototype.mths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    Date.prototype.wd = ['Sunday', 'Monday', 'Tuesday', 'Wednesday','Thursday', 'Friday', 'Saturday'];
    Date.prototype.dim = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    Date.prototype.strftime_f = {
        A: function (d) { return d.wd[d.getDay()] },
        a: function (d) { return d.wd[d.getDay()].substring(0,3) },
        B: function (d) { return d.mths[d.getMonth()] },
        b: function (d) { return d.mths[d.getMonth()].substring(0,3) },
        C: function (d) { return Math.floor(d.getFullYear()/100) },
        c: function (d) { return d.toString() },
        D: function (d) { return d.strftime_f.m(d) + '/' + d.strftime_f.d(d) + '/' + d.strftime_f.y(d) },
        d: function (d) { return d.getDate().pad(2,'0') },
        e: function (d) { return d.getDate().pad(2,' ') },
        F: function (d) { return d.strftime_f.Y(d) + '-' + d.strftime_f.m(d) + '-' + d.strftime_f.d(d) },
        H: function (d) { return d.getHours().pad(2,'0') },
        I: function (d) { return ((d.getHours() % 12 || 12).pad(2)) },
        j: function (d) {
            var t = d.getDate()
            var m = d.getMonth() - 1
            if (m > 1) {
                var y = d.getYear()
                if (((y % 100) == 0) && ((y % 400) == 0)) {
                    ++t
                } else if ((y % 4) == 0) {
                    ++t
                }
            }
            while (m > -1) t += d.dim[m--]
            return t.pad(3,'0')
        },
        k: function (d) { return d.getHours().pad(2,' ') },
        l: function (d) { return ((d.getHours() % 12 || 12).pad(2,' ')) },
        M: function (d) { return d.getMinutes().pad(2,'0') },
        m: function (d) { return (d.getMonth()+1).pad(2,'0') },
        n: function (d) { return "\n" },
        p: function (d) { return (d.getHours() > 11) ? 'PM' : 'AM' },
        R: function (d) { return d.strftime_f.H(d) + ':' + d.strftime_f.M(d) },
        r: function (d) { return d.strftime_f.I(d) + ':' + d.strftime_f.M(d) + ':' + d.strftime_f.S(d) + ' ' + d.strftime_f.p(d) },
        S: function (d) { return d.getSeconds().pad(2,'0') },
        s: function (d) { return Math.floor(d.getTime()/1000) },
        T: function (d) { return d.strftime_f.H(d) + ':' + d.strftime_f.M(d) + ':' + d.strftime_f.S(d) },
        t: function (d) { return "\t" },
        u: function (d) { return(d.getDay() || 7) },
        v: function (d) { return d.strftime_f.e(d) + '-' + d.strftime_f.b(d) + '-' + d.strftime_f.Y(d) },
        w: function (d) { return d.getDay() },
        X: function (d) { return d.toTimeString() }, // wrong?
        x: function (d) { return d.toDateString() }, // wrong?
        Y: function (d) { return d.getFullYear() },
        y: function (d) { return (d.getYear() % 100).pad(2) },
        '%': function (d) { return '%' }
    }

    Date.prototype.strftime_f['+'] = Date.prototype.strftime_f.c
    Date.prototype.strftime_f.h = Date.prototype.strftime_f.b

    Date.prototype.strftime = function (f) {
        var r = '', n = 0, c
        while (n < f.length) {
            c = f.substring(n, n+1)
            if (c == '%') {
                c = f.substring(++n, n+1)
                r += (this.strftime_f[c]) ? this.strftime_f[c](this) : c
            } else {
                r += c
            }
            ++n
        }

        return r
    };

    // TODO: Still some work to do on this for certain cases such as %b
    Date.prototype.strptime_f = {
        'Y': new RegExp('^-?[0-9]+'),
        'd': new RegExp('^[0-9]{1,2}'),
        'm': new RegExp('^[0-9]{1,2}'),
        'H': new RegExp('^[0-9]{1,2}'),
        'M': new RegExp('^[0-9]{1,2}'),
        'a': new RegExp('(Sun|Mon|Tue|Wed|Thu|Fri|Sat|Sun)'),
        'A': new RegExp('(Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday)'),
        'b': new RegExp('(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)'),
        'B': new RegExp('(January|February|March|April|May|June|July|August|September|October|November|December)')
    }

    Date.prototype._strptime = function (s, f) {
        var p = {}, d, v, k, n
        for (var i=0, o=0; i < f.length; i++, o++) {
            var k = f.substr(i,1)
            var n = s.substr(o,1)
            if (k == '%') {
                k = f.substr(++i, 1)
                d = this.strptime_f[k].exec(s.substring(o))
                if (d.length == 0) {
                    return null
                }
                d = d[0]
                o+= d.length-1
                v = d
                if (k == 'B') {
                    p['m'] = this.mths.search(v)+1
                } else {
                    p[k] = v
                }
                continue
            } else if (k != n) {
                return null
            }
        }

        return p
    }

    Date.prototype.strptime = function (s, f) {
        var p, y
        if (p = this._strptime(s, f)) {
            this.setFullYear(p['Y'] ? p['Y'] : 0)
            this.setMonth(p['m'] ? p['m']-1 : 0)
            this.setDate(p['d'] ? p['d'] : 1)
            if (p['H']) this.setHours(p['H'])
            if (p['M']) this.setMinutes(p['M'])

            return true
        }

        return false
    }

    FLQ.shortcut = {}
    FLQ.shortcut.enabled = false
    FLQ.shortcut.events = {}

    FLQ.shortcut.add = function (s, f) {
        if (!FLQ.shortcut.enabled) FLQ.shortcut.enable()
        s = FLQ.shortcut.parse(s)
        if (!FLQ.isFunc(FLQ.shortcut.events[s])) {
            FLQ.shortcut.events[s] = f
        } else {
            throw('A function is already registered to the shortcut '+s)
        }
    }

    FLQ.shortcut.remove = function (s) {
        s = FLQ.shortcut.parse(s)
        if (FLQ.shortcut.events[s]) delete FLQ.shortcut.events[s]
    }

    /**
     * parse is used to normalise the format of the shortcut signature
     * passed to add so Ctrl+Shift+A would be the same as Ctrl+A+Shift
     * for example
     */
    FLQ.shortcut.parse = function (s) {
        s = s.toLowerCase()
        var p = s.split('+')
        var ctrl = false, alt = false, shift = false, esc = false, char = ''
        if (p && p.length > 0) {
            for (var i=0; i < p.length; i++) {
                switch (p[i]) {
                    case 'ctrl': ctrl = true; break
                    case 'alt': alt = true; break
                    case 'shift': shift = true; break
                    case 'esc':
                    default:
                        char+= (char.length > 0 ? '+' : '') + p[i]
                        break
                }
            }

            var ns = ''
            if (ctrl) ns+= 'ctrl'
            if (alt) ns+= (ns.length > 0 ? '+' : '') + 'alt'
            if (shift) ns+= (ns.length > 0 ? '+' : '') + 'shift'
            if (esc) ns+= (ns.length > 0 ? '+' : '') + 'esc'
            ns+= (ns.length > 0 ? '+' : '') + char

            return ns
        }

        return s
    }

    FLQ.shortcut.handle = function (e) {
        var t = FLQ.event.getTarget(e)
        if (t.nodeName != 'INPUT' || t.nodeName != 'SELECT') {
            var s = '';
            if (e.ctrlKey)  s+= 'ctrl';
            if (e.altKey)   s+= (s.length > 0 ? '+' : '') + 'alt'
            if (e.shiftKey) s+= (s.length > 0 ? '+' : '') + 'shift'
            if (e.keyCode == 27) {
                s+= (s.length > 0 ? '+' : '') + 'esc'
            } else {
                var c = FLQ.event.getCharCode(e)
                if (c > 0) s+= (s.length > 0 ? '+' : '') + String.fromCharCode(c).toLowerCase()
            }

            if (FLQ.isFunc(FLQ.shortcut.events[s])) {
                FLQ.shortcut.events[s](e)
                FLQ.event.stopEvent(e)
            }
        }
    }

    FLQ.shortcut.catchKeys = function (e) {
        var t = FLQ.event.getTarget(e)
        if (t.nodeName != 'INPUT' || t.nodeName != 'SELECT') {
            var s = ''
            if (e.ctrlKey)  s+= 'ctrl'
            if (e.altKey)   s+= (s.length > 0 ? '+' : '') + 'alt'
            if (e.shiftKey) s+= (s.length > 0 ? '+' : '') + 'shift'
            if (e.keyCode == 27) {
                s+= (s.length > 0 ? '+' : '') + 'esc'
            } else {
                var c = FLQ.event.getCharCode(e)
                if (c > 0) s+= (s.length > 0 ? '+' : '') + String.fromCharCode(c).toLowerCase()
            }

            if (FLQ.isFunc(FLQ.shortcut.events[s])) {
                FLQ.event.stopEvent(e)
            }
        }
    }

    FLQ.shortcut.enable = function () {
        FLQ.event.add(document, 'keydown', FLQ.shortcut.handle)
        // This was added to fix problem with Ctrl+A in Opera
        FLQ.event.add(document, 'keypress', FLQ.shortcut.catchKeys)
        FLQ.shortcut.enabled = true
    }

    FLQ.ajax = function (url, callback) {
        this.url          = url
        this.handlers     = {}
        this.headers      = {}
        this.method       = 'GET'
        this.asynchronous = true
        this.svr          = null

        this.addHandler = function (func, status, state) {
            if (arguments.length < 2) status = 200
            if (arguments.length < 3) state = 4 // 4 = ready state complete

            if (FLQ.isFunc(func)) {
                if (!FLQ.isObj(this.handlers[state])) this.handlers[state] = {}
                this.handlers[state][status] = func
            }
        }

        if (arguments.length > 1) this.addHandler(callback)
    }

    FLQ.ajax.prototype.addHeader = function (key, val) {
        this.headers[key] = val
    }

    FLQ.ajax.prototype.send = function (data) {
        if (arguments.length < 1) data = ''

        if (this.url == null) throw('FLQ.ajax.send() - url must be specified')

        var svr = false
        if (FLQ.isSet(typeof ActiveXObject)) {
            try {
                svr = new ActiveXObject("Msxml2.XMLHTTP")
            } catch (e) {
                try {
                    svr = new ActiveXObject("Microsoft.XMLHTTP")
                } catch (e) {
                    svr = false
                }
            }
        } else if (window.XMLHttpRequest) {
            try {
                svr = new XMLHttpRequest()
            } catch (e) {
                svr = false
            }
        }

        if (svr) {
            var a = this
            svr.onreadystatechange = function () {
                try {
                    var state = svr.readyState
                } catch (e) {
                    return false
                }
                try {
                    var status = svr.status
                } catch (e) {
                    return false
                }
                a.state = state
                if (FLQ.isSet(typeof a.handlers[state]) && FLQ.isFunc(a.handlers[state][status])) {
                    a.handlers[state][status](svr)
                } else if (FLQ.isSet(typeof a.handlers[state]) && FLQ.isFunc(a.handlers[state][''])) {
                    a.handlers[state][''](svr)
                }
            }

            svr.open(this.method, this.url, this.asynchronous)
            for (var i in this.headers) {
                svr.setRequestHeader(i, this.headers[i])
            }

            svr.send(data)
        }
    };
