
    if (typeof FLQ == 'undefined') var FLQ = new Object()

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
        var m = n.className.split(/ +/)
        for (i in m) {
            if (m[i] == c) return true
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
        window.open(url, n, s);
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
