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

    /**
     * FLQ.event - Fliquid event handling library
     *
     * Version: 1.1.0 BETA
     * Last Modified: 24/05/2009
     */

    if (typeof FLQ == 'undefined') var FLQ = new Object()

    FLQ.event = new Object()

    FLQ.event.f = {}

    /**
     * addListener() will add a listener to an element for a given event
     */
    FLQ.event.addListener = function(n, e, f, cap) {
        if (typeof n == 'string') n = document.getElementById(n)
        if (typeof n != 'object') throw(n+' is not an object')
        if (typeof n.addEventListener != 'undefined') {
            n.addEventListener(e, f, cap)
        } else if (typeof n.attachEvent != 'undefined') {
            n.attachEvent('on' + e, FLQ.event.getFunc(n, e, f))
        }
    }

    FLQ.event.add = FLQ.event.addListener

    /**
     * getXY() will return an array of the X and Y coord's of the event
     */
    FLQ.event.getXY = function(e) {
        if (typeof e == 'undefined') {
            e = window.event
        }
        var xy = [e.pageX, e.pageY]
        return xy
    }

    /**
     * removeListener() will remove a listener from an element for a given event
     */
    FLQ.event.removeListener = function(n, e, f, cap) {
        if (typeof n == 'string') n = document.getElementById(n)
        if (typeof n != 'object') throw(n+' is not an object')
        if (typeof n.removeEventListener != 'undefined') {
            n.removeEventListener(e, f, cap)
        } else if (typeof n.detachEvent != 'undefinded') {
            var f = FLQ.event.delFunc(n, e, f)
            n.detachEvent('on' + event, f)
        }
    }

    FLQ.event.getFunc = function (n, ev, f) {
        if (typeof FLQ.event.f[ev] == 'undefined') FLQ.event.f[ev] = {}
        if (typeof FLQ.event.f[ev][n] == 'undefined') FLQ.event.f[ev][n] = {}
        var nf = function (e) { if (!e) e = window.event; f.apply(n, [e]) }
        FLQ.event.f[ev][n][f] = {func : nf}
        return nf
    }

    FLQ.event.delFunc = function (n, ev, f) {
        if (FLQ.event.f && FLQ.event.f[ev] && FLQ.event.f[ev][n] && FLQ.event.f[ev][n][f]) {
            var nf = FLQ.event.f[ev][n][f]['func']
            delete FLQ.event.f[ev][n][f]
            return nf
        }

        return f
    }

    /**
     * getTarget returns the target element of the event.
     */
    FLQ.event.getTarget = function(e) {
        if (typeof e.target != 'undefined') {
            return e.target
        } else if (typeof e.srcElement != 'undefined') {
            return e.srcElement
        }
    }

    /**
     * stopProp will stop the event from propagating or bubbling.
     */
    FLQ.event.stopProp = function(e) {
        if (typeof e.stopPropagation != 'undefined') {
            e.stopPropagation()
        } else if (typeof e.cancelBubble != 'undefined') {
            e.cancelBubble = true
        }
    }

    /**
     * prevDef will prevent the default event action from occuring (preventDefault)
     */
    FLQ.event.prevDef = function(e) {
        if (typeof e.preventDefault != 'undefined') {
            e.preventDefault()
        } else {
            e.returnValue = false
        }
    }

    /**
     * stopEvent is a wrapper for stopProp and prevDef methods
     */
    FLQ.event.stopEvent = function (e) {
        FLQ.event.stopProp(e)
        FLQ.event.prevDef(e)
    }

    FLQ.e = FLQ.event;
