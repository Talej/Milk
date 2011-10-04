    /**
     * Copyright 2009 Michael Little, Christian Biggins
     *
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with this program. If not, see <http://www.gnu.org/licenses/>.
     */

    if (typeof FLQ == 'undefined') var FLQ = function () { }

    /**
     * FLQ.URL - Fliquid URL building/handling class
     *
     * Version: 1.0.0 BETA
     */
    FLQ.URL = function (url) {
        this.scheme = null
        this.host   = null
        this.port   = null
        this.path   = null
        this.args   = {}
        this.anchor = null

        if (arguments.length > 0) this.set(url)
    }

    /**
     * thisURL() parses the current window.location and returns a FLQ.URL object
     */
    FLQ.URL.thisURL = function () {
        return new FLQ.URL(window.location.href)
    }

    FLQ.URL.prototype = {}

    /**
     * set() parses a url and sets the properties of the FLQ.URL object
     */
    FLQ.URL.prototype.set = function (url) {
        var p
        if (p = this.parseURL(url)) {
            this.scheme = p['scheme']
            this.host   = p['host']
            this.port   = p['port']
            this.path   = p['path']
            this.args   = this.parseArgs(p['args'])
            this.anchor = p['anchor']
        }
    }

    /**
     * removeArg() is used remove a specified argument from the FLQ.URL object arguments
     */
    FLQ.URL.prototype.removeArg = function (k) {
        if (k && String(k.constructor) == String(Array)) { // TODO: Change to use is_array
            var t = this.args, i
            for (i=0; i < k.length-1; i++) {
                if (typeof t[k[i]] != 'undefined') { // TODO: Change to use isset
                    t = t[k[i]]
                } else {
                    return false
                }
            }
            delete t[k[k.length-1]]
            return true
        } else if (typeof this.args[k] != 'undefined') { // TODO: Change to use isset
            delete this.args[k]
            return true
        }

        return false
    }

    /**
     * addArg() is used to add an argument with specified value to the FLQ.URL object arguments
     */
    FLQ.URL.prototype.addArg = function (k, v, o) {
        if (k && String(k.constructor) == String(Array)) { // TODO: Change to use is_array
            var t = this.args, i
            for (i=0; i < k.length-1; i++) {
                if (typeof t[k[i]] == 'undefined') t[k[i]] = {}
                t = t[k[i]]
            }
            if (o || typeof t[k[k.length-1]] == 'undefined') t[k[k.length-1]] = v // TODO: Change to use isset
        } else if (o || typeof this.args[k] == 'undefined') { // TODO: Change to use isset
            this.args[k] = v
            return true
        }

        return false
    }

    /**
     * parseURL() parses the specified url and returns an object containing the various components
     */
    FLQ.URL.prototype.parseURL = function (url) {
        // TODO: Add support for ftp username
        var p = {}, m
        if (m = url.match(/((s?ftp|https?):\/\/)?([^\/:\?]+)?(:([0-9]+))?([^\?#]+)?(\?([^#]+))?(#(.+))?/)) {
            p['scheme'] = (m[2]  ? m[2] : 'http')
            p['host']   = (m[3]  ? m[3] : window.location.host)
            p['port']   = (m[5]  ? m[5] : null)
            p['path']   = (m[6]  ? m[6] : null)
            p['args']   = (m[8]  ? m[8] : null)
            p['anchor'] = (m[10] ? m[10] : null)

            return p
        }

        return false
    }

    /**
     * parseArgs() parses a query string and returns an object containing the parsed data
     */
    FLQ.URL.parseArgs = function (s) {
        var a = {}, kp, kv, p, i, z, t, o
        if (s && s.length) {
            if ((kp = s.split('&')) && kp.length) {
                for (i=0; i < kp.length; i++) {
                    if ((kv = kp[i].split('=')) && kv.length == 2) {
                        kv[0] = FLQ.URL.decode(kv[0])
                        kv[1] = FLQ.URL.decode(kv[1])
                        if (p = kv[0].split(/(\[|\]\[|\])/)) {
                            if (p[p.length-1] == '') p.splice(p.length-1, 1)
                            for (z=0; z < p.length; z++) {
                                if (p[z] == ']' || p[z] == '[' || p[z] == '][') p.splice(z, 1)
                            }
                            t = a
                            for (o=0; o < p.length-1; o++) {
                                if (typeof t[p[o]] == 'undefined') t[p[o]] = {} // TODO: Change this to isset
                                t = t[p[o]]
                            }
                            t[p[p.length-1]] = kv[1]
                        } else {
                            a[kv[0]] = kv[1]
                        }
                    }
                }
            }
        }

        return a
    }

    FLQ.URL.prototype.parseArgs = function (s) {
        return FLQ.URL.parseArgs(s)
    }

    /**
     * toArgs() takes an object and returns a query string
     */
    FLQ.URL.prototype.toArgs = function (a, p) {
        if (arguments.length < 2) p = '';
        if (a && typeof a == 'object') { // TODO: Change this to use is_object
            var s = '', k, i
            for (i in a) {
                if (typeof a[i] != 'function') {
                    if (s.length) s+= '&'
                    if (a[i] == null) {
                        k = (p.length ? p+'['+i+']' : i)
                        s+= k+'='
                    } else if (typeof a[i] == 'object') { // TODO: Change this to use is_object
                        k = (p.length ? p+'['+i+']' : i)
                        s+= this.toArgs(a[i], k)
                    } else { // TODO: Change this to use is_function
                        s+= p+(p.length && i != '' ? '[' : '')+i+(p.length && i != '' ? ']' : '')+'='+a[i]
                    }
                }
            }

            return s
        }

        return ''
    }

    /**
     * toAbsolute() returns a string containing the absolute URL for the current FLQ.URL object
     */
    FLQ.URL.prototype.toAbsolute = function () {
        var s = ''
        if (this.scheme != null) s+= this.scheme+'://'
        if (this.host != null) s+= this.host
        if (this.port != null) s+= ':'+this.port
        s+= this.toRelative()

        return s
    }

    /**
     * toRelative() returns a string containing the relative URL for the current FLQ.URL object
     */
    FLQ.URL.prototype.toRelative = function () {
        var s = ''
        if (this.path != null) s+= this.path
        var a = this.toArgs(this.args)
        if (a.length) s+= '?'+a
        if (this.anchor != null) s+= '#'+this.anchor

        return s
    }

    /**
     * isHost() is used to determine whether the host in the FLQ.URL object matches the current host
     */
    FLQ.URL.prototype.isHost = function () {
        var u = FLQ.URL.thisURL()
        return (this.host == null || this.host == u.host ? true : false)
    }

    /**
     * toString() returns a string containing the current FLQ.URL object as a URL
     */
    FLQ.URL.prototype.toString = function () {
        return (this.isHost() ? this.toRelative() : this.toAbsolute())
    }

    FLQ.URL.encode = function (s) {
        var r = '', c, i, cs = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890$-_.!*\'(),'
        for (i=0; i < s.length; i++) {
            c = s.substr(i,1)
            if (c == ' ') {
                c = '+'
            } else if (cs.indexOf(c) == -1) {
                c = '%'+FLQ.toHex(c)
            }
            r += c
        }

        return r
    }

    FLQ.URL.decode = function (s) {
        var r = '', c, i
        for (i=0; i < s.length; i++) {
            c = s.substr(i,1)
            if (c == '+') {
                c = ' '
            } else if (c == '%' && s.substr(i+1,1) != '%') {
                c = FLQ.fromHex(s.substr(i+1,2))
                i+=2
            }
            r += c
        }

        return r
    };
