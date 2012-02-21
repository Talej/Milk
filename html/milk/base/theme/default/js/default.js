
    var _$ = function (i) { return document.getElementById('mlk-'+i) }

    var Milk = {
        SLOT_SAMEWIN   : '_self',
        SLOT_NEWWIN    : '_blank',
        SLOT_CHILDWIN  : '_child',
        SLOT_LIGHTWIN  : '_light',
        SLOT_AJAX      : '_ajax',
        SLOT_LAUNCHER  : '_launcher',
        errors         : [],
        history        : [],
        c              : {},
        saveGroups     : {},
        NOTIFY_ERROR   : 'error',
        NOTIFY_WARNING : 'warning',
        NOTIFY_MESSAGE : 'message'
    }

    Milk.add = function (ctrl, id, props) {
        // TODO: Check that the control object exists
        // TODO: Check that a control with that id doesn't exist
        var c = new Milk.Ctrl[ctrl](id)
        c.setProps(props)
        if (c.saveGroup) c.setSaveGroup(c.saveGroup)
        Milk.c[id] = c
        return c
    }

    Milk.get = function (id) {
        if (Milk.c[id]) return Milk.c[id]
    }

    Milk.Ctrl = {}

    Milk.getArg = function (args, arg) {
        if (FLQ.isObj(args) && FLQ.isSet(typeof args[arg])) {
            return args[arg]
        }

        return null
    }

    Milk.mergeArgs = function () {
        var args = {}, i, j
        for (i=0; i < arguments.length; i++) {
            if (FLQ.isObj(arguments[i])) {
                for (j in arguments[i]) {
                    if (!FLQ.isFunc(arguments[i][j])) args[j] = arguments[i][j]
                }
            }
        }

        return args
    }

    Milk.getLauncher = function () {
        var a
        if (window.parent && window.parent.FLQ && window.parent.FLQ._ && window.parent.FLQ._['lbox']) {
            a = window.parent.FLQ.lbox.getArgs()
            if (FLQ.isObj(a) && FLQ.isSet(typeof a['launcher']) && window.parent.Milk) {
                return window.parent.Milk.get(a['launcher'])
            }
        } else {
            a = FLQ.popup.getArgs(), o = FLQ.popup.getLauncher()
            if (FLQ.isObj(a) && FLQ.isSet(typeof a['launcher']) && o !== null && FLQ.isSet(typeof o.Milk)) {
                return o.Milk.get(a['launcher'])
            }
        }

        return null
    }

    Milk.editHistory = function (k, v) {
        if (Milk.history.length > 0) {
            var u = new FLQ.URL(Milk.history[Milk.history.length-1])
            if (v === null) {
                u.removeArg(k)
            } else {
                u.addArg(k, v, true)
            }

            Milk.history[Milk.history.length-1] = u.toString()
        }
    }

    Milk.notify = function (t, err) {
        var s = ''
        for (var i=0; err[i]; i++) {
            s+= '- '+err[i]+'\n'
        }
        alert(s)
    }

    /**
     * Connection handling
     */
    Milk.Conn = function (src, signal, destId, slot, args) {
        this.src    = src
        this.signal = signal
        this.destId = destId
        this.slot   = slot
        this.args   = args

        if ((this.slot == 'back' || this.slot == 'reload' || this.slot == 'refresh') && Milk.getArg(args, 'validate') != true) {
            if (!FLQ.isObj(this.args)) this.args = []
            this.args['validate'] = false
        }

        if (
            this.destId == Milk.SLOT_SAMEWIN ||
            this.destId == Milk.SLOT_NEWWIN ||
            this.destId == Milk.SLOT_CHILDWIN ||
            this.destId == Milk.SLOT_LIGHTWIN ||
            this.destId == Milk.SLOT_AJAX
        ) {
            this.dest = this.destId
            this.exec = Milk.Conn.serverExec
        } else {
            this.dest = (this.destId == Milk.SLOT_LAUNCHER ? Milk.getLauncher() : Milk.get(this.destId))
            this.exec = Milk.Conn.clientExec
        }
    }

    Milk.Conn.clientExec = function (args, e) {
        args = Milk.mergeArgs(this.args, args)
        // Add payload from controls

        if (!FLQ.isSet(typeof args['send']) || args.send === true) {
            for (var i in Milk.c) {
                if ((!FLQ.isSet(typeof args.send) && Milk.c[i].defaultSend) || args.send) {
                    if (Milk.c[i].getData) args = Milk.mergeArgs(args, Milk.c[i].getData())
                    if (!FLQ.isSet(typeof args.validate) || args.validate) {
                        if (Milk.c[i].validate) {
                            Milk.c[i].validate()
                        }
                    }
                }
            }

            if (Milk.errors[0]) {
                Milk.notify(Milk.NOTIFY_ERROR, Milk.errors)
                return false
            }
        }

        if (FLQ.isSet(typeof args.send)) delete args.send
        if (FLQ.isSet(typeof args.validate)) delete args.validate

        if (FLQ.isSet(typeof args.confirm)) {
            if (!confirm(args.confirm)) return
            delete args.confirm
        }

        this.dest.execSlot(this.slot, args, e)
    }

    Milk.Conn.serverExec = function (args) {
        args = Milk.mergeArgs(args, this.args)
        if (
            this.dest == Milk.SLOT_SAMEWIN ||
            this.dest == Milk.SLOT_NEWWIN ||
            this.dest == Milk.SLOT_CHILDWIN ||
            this.dest == Milk.SLOT_LIGHTWIN ||
            this.dest == Milk.SLOT_AJAX
        ) {
            var frm = document.createElement('form')
            frm.setAttribute('method', 'get')
            if (Milk.getArg(args, 'modurl')) {
                frm.setAttribute('action', args.modurl)
                delete args.modurl
            } else {
                frm.setAttribute('action', window.location.pathname)
            }

            function add_field(frm, key, val) {
                if (key == null) return
                if (FLQ.isObj(key)) {
                    for (var i in key) {
                        if (!FLQ.isFunc(key[i])) {
                            add_field(frm, i, key[i])
                        }
                    }
                } else if (FLQ.isObj(val)) {
                    for (var i in val) {
                        if (!FLQ.isFunc(val[i])) {
                            try { add_field(frm, key+'['+i+']', val[i]) } catch(e) { }
                        }
                    }
                } else {
                    if (FLQ.isBool(val)) {
                        val = (val ? '1' : '0')
                    }
                    var s = document.createElement('span')
                    s.innerHTML = '<input type="hidden" />' // can not change type in IE
                    s.firstChild.setAttribute('name', key)
                    s.firstChild.setAttribute('value', FLQ.ifNull(val,''))
                    frm.appendChild(s.firstChild)
                }
            }

            // Confirm with the user if required
            if (Milk.getArg(args, 'confirm')) {
                if (!confirm(args.confirm)) return
                delete args.confirm
            }

            // Add payload from controls
            if (!FLQ.isSet(typeof args.send) || args.send === true) {
                frm.setAttribute('method', 'post')
                for (var i in Milk.c) {
                    if ((!FLQ.isSet(typeof args.send) && Milk.c[i].defaultSend) || args.send) {
                        if (Milk.c[i].addData) Milk.c[i].addData(frm)
                        if (Milk.c[i].getData) add_field(frm, Milk.c[i].getData())
                        if (!FLQ.isSet(typeof args.validate) || args.validate) {
                            if (Milk.c[i].validate) Milk.c[i].validate()
                        }
                    }
                }
                if (Milk.errors[0]) {
                    Milk.notify(Milk.NOTIFY_ERROR, Milk.errors)
                    return false
                }
            }
            if (FLQ.isSet(typeof args.send)) delete args.send
            if (FLQ.isSet(typeof args.validate)) delete args.validate

            if (this.dest == Milk.SLOT_SAMEWIN) {
                frm.setAttribute('target', '_self')

                // Add history to the form submission
                if (!FLQ.isSet(typeof args.nohistory)) {
                    for (var i = 0; Milk.history[i]; i++) {
                        add_field(frm, 'history['+i+']', Milk.history[i])
                    }
                } else {
                    delete args.nohistory
                }
            } else if (this.dest == Milk.SLOT_NEWWIN) {
                if (FLQ.isSet(typeof args.target)) {
                    frm.setAttribute('target', args.target)
                    delete args.target
                } else {
                    frm.setAttribute('target', '_blank')
                }
            } else if (this.dest == Milk.SLOT_CHILDWIN) {
                if (FLQ.isSet(typeof args.target)) {
                    var t = args.target
                } else {
                    var t = 'newwin_'+(this.src ? this.src.id : 'unknown')
                }
                var props = {
                    'width'     : (FLQ.isSet(typeof args.width) ? args.width : 600),
                    'height'    : (FLQ.isSet(typeof args.height) ? args.height : 600),
                    'name'      : t,
                    'dependant' : true,
                    'args'      : {'launcher' : (this.src ? this.src.id : null)},
                    'status'    : 'yes'
                }
                var w = FLQ.popup('/milk/blank.php', props)
                frm.setAttribute('target', t)

                delete args.width
                delete args.height
                delete args.target
            } else if (this.dest == Milk.SLOT_LIGHTWIN) {
                if (FLQ.isSet(typeof args.target)) {
                    var t = args.target
                } else {
                    var t = 'newwin_'+(this.src ? this.src.id : 'unknown')
                }
                var props = {
                    'width'     : (FLQ.isSet(typeof args.width) ? args.width : 600),
                    'height'    : (FLQ.isSet(typeof args.height) ? args.height : 400),
                    'name'      : t,
                    'args'      : {'launcher' : (this.src ? this.src.id : null)}
                }
                var w = FLQ.lbox('/milk/blank.php', props)
                if (!FLQ.isSet(typeof args.height) && w) {
                    FLQ.e.add(w, 'load', function () { setTimeout(FLQ.lbox.autoHeight, 300) })
                }
                frm.setAttribute('target', t)

                delete args.width
                delete args.height
                delete args.target
            }

            var ajaxResponse = {}
            if (this.dest === Milk.SLOT_AJAX && FLQ.isSet(typeof args.response)) {
                ajaxResponse = args.response
                delete args.response
            }

            // Add remaining arguments
            if (!FLQ.isSet(typeof args.nohistory)) {
                add_field(frm, 'act', this.slot)
            }
            delete args.nohistory
            add_field(frm, args)

            if (this.dest == Milk.SLOT_AJAX) {
                add_field(frm, 'ajax', 1)
                var ajax = new FLQ.ajax(frm.getAttribute('action'))
                ajax.method = 'POST'
                var data = ''
                for (var n,i = 0; frm.childNodes[i]; i++) {
                    n = frm.childNodes[i]
                    if (n.nodeType == 1 && n.nodeName == 'INPUT' && n.getAttribute('type') == 'hidden' && !(/^response\[/.exec(n.name))) {
                        if (data.length > 0) data += '&'
                        // without encodeURIComponent characters like & break the post
                        // but using escape will break unicode characters.
                        // Is there any problems with using encodeURIComponent?
                        data += escape(n.name)+'='+encodeURIComponent(n.value)
                    }
                }
                for (var i in ajaxResponse) {
                    if (!FLQ.isFunc(ajaxResponse[i])) {
                        ajax.addHandler(Milk.Conn.ajaxResponse(ajaxResponse[i].dest, ajaxResponse[i].slot, ajaxResponse[i].args), i)
                    }
                }
                ajax.addHandler(Milk.Conn.ajaxShowErrors, '')
                ajax.addHandler(Milk.Conn.ajaxUnauthorised, 401)
                ajax.addHeader('Content-Type', 'application/x-www-form-urlencoded')
                ajax.send(data)
                if (frm.parentNode) frm.parentNode.removeChild(frm)
            } else {
                // append the form and submit
                document.body.appendChild(frm)
                frm.submit() // Note: Could be a problem with IE requiring setTimeout to be used
                if (this.dest == Milk.SLOT_NEWWIN || this.dest == Milk.SLOW_CHILDWIN) {
                    if (frm.parentNode) frm.parentNode.removeChild(frm)
                }
            }
        }
    }

    Milk.Conn.ajaxResponse = function (dest, slot, args) {
        var c = new Milk.Conn(null, null, dest, slot, args)
        var f = function (svr) {
            c.exec({'svr':svr, 'validate':false, 'require':false})
            Milk.Conn.ajaxShowErrors(svr)
        }

        return f
    }

    Milk.Conn.ajaxShowErrors = function (svr) {
        if (svr.responseXML) {
            var i, r, u, m, s = [], e = svr.responseXML.getElementsByTagName('error')
            for (i=0; e[i]; i++) {
                if (e[i].firstChild.nodeType == 3) {
                    s.push(e[i].firstChild.nodeValue)
                }
            }
            
            if (s[0]) {
                Milk.notify(Milk.NOTIFY_ERROR, s)
            } else {
                m = svr.responseXML.getElementsByTagName('message')
                for (i=0; m[i]; i++) {
                   if (m[i].firstChild.nodeType == 3) {
                        s.push(m[i].firstChild.nodeValue)
                    }
                }

                r = FLQ.ajax.getValue(svr.responseXML, 'redirect')
                if (r) {
                    u = new FLQ.URL(r)
                    u.addArg('messages', s)
                    u.redirect()
                } else {
                    if (s[0]) Milk.notify(Milk.NOTIFY_MESSAGE, s)
                }
            }
        }
    }

    Milk.Conn.ajaxUnauthorised = function () {
        new Milk.Conn(null, null, Milk.SLOT_SAMEWIN, 'refresh').exec({'send':false, 'validate':false})
    }

    var MilkCtrl = function () {
        this.id          = null
        this.signals     = null
        this.strictConns = true
        this.saveGroup   = null
    }

    MilkCtrl.prototype = {}

    MilkCtrl.prototype.init = function () { }

    MilkCtrl.prototype.setProps = function (props) {
        // TODO: Check if props is object
        for (var i in props) {
            this[i] = props[i]
        }
    }

    MilkCtrl.prototype.connect = function (signal, destId, slot, args) {
        if (this.signals === null) this.signals = {}
        if (!FLQ.isSet(typeof this.signals[signal])) this.signals[signal] = []
        if (!FLQ.isObj(args)) args = {}
        this.signals[signal].push(new Milk.Conn(this, signal, destId, slot, args))
    }

    MilkCtrl.prototype.sendSignal = function (signal, args, e) {
        if (this.hasSignal(signal)) {
            if (!FLQ.isObj(args)) args = {}
            var newargs = this.mergeSignalArgs(signal, args)
            if (FLQ.isObj(newargs)) {
                for (var i=0; this.signals[signal][i]; i++) {
                    this.signals[signal][i].exec(newargs, e)
                }
            }
        }
    }

    MilkCtrl.prototype.hasSignal = function (signal) {
        return (FLQ.isObj(this.signals) && FLQ.isSet(typeof this.signals[signal]) && FLQ.isArr(this.signals[signal]) ? true : false)
    }

    MilkCtrl.prototype.hasSlot = function (slot) {
        return ((FLQ.isSet(typeof this[slot]) && FLQ.isFunc(this[slot])) ? true : false)
    }

    MilkCtrl.prototype.hasValue = function (v) {
        if (v === null) {
            return false
        } else if ((FLQ.isStr(v) || FLQ.isArr(v)) && v.length == 0) {
            return false
        } else if (FLQ.isObj(v)) {
            for (var i in v) {
                if (!FLQ.isFunc(v[i])) {
                    return true
                }
            }

            return false
        }

        return true
    }

    MilkCtrl.prototype.mergeSignalArgs = function (signal, args) {
        if (this.hasSignal(signal)) {
            var send = false
            var require = false
            for (var i=0; this.signals[signal][i]; i++) {
                if ((this.signals[signal][i].args && this.signals[signal][i].args['send']) || this.defaultSend) send = true
                if ((this.signals[signal][i].args && this.signals[signal][i].args['require']) || this.defaultRequire) require = true
            }
            if (FLQ.isSet(typeof args['send'])) send = args['send']
            if (FLQ.isSet(typeof args['require'])) require = args['require']

            var v = null
            if (FLQ.isFunc(this.getValue)) v = this.getValue()
            if (require && !this.hasValue(v)) {
                alert('An item must be selected')
                var args = null
            } else if (this.hasValue(v)) {
                var args = Milk.mergeArgs(args, v)
            }
        }

        return args
    }

    MilkCtrl.prototype.execSlot = function (slot, args, e) {
        if (this.hasSlot(slot)) {
            this[slot](args, e)
            if (!this.strictConns) {
                if (FLQ.isSet(typeof args['svr'])) delete args['svr']
                this.sendSignal(slot, args, e)
            }
        } else if (!this.strictConns) {
            if (FLQ.isSet(typeof args['svr'])) delete args['svr']
            this.sendSignal(slot, args, e)
        } else {
            throw('Unable to execute slot '+slot)
        }
    }

    MilkCtrl.prototype.setSaveGroup = function (g) {
        if (FLQ.isStr(g) && g.length > 0) {
            if (!FLQ.isSet(typeof Milk.saveGroups[g])) Milk.saveGroups[g] = []
            Milk.saveGroups[g].push(this)
            this.saveGroup = Milk.saveGroups[g]
            if (this.hasChanged) {
                this._hasChanged = this.hasChanged
                this.hasChanged = this.hasGroupChanged
            }
        }
    }

    MilkCtrl.prototype.hasGroupChanged = function () {
        if (FLQ.isArr(this.saveGroup)) {
            for (var i=0; this.saveGroup[i]; i++) {
                if (this.saveGroup[i]._hasChanged()) {
                    return true
                }
            }
        }

        return false
    }

    MilkCtrl.prototype.refresh = function () {
        new Milk.Conn(null, null, Milk.SLOT_SAMEWIN, 'refresh').exec({'send':true,'validate':false})
    }

    MilkCtrl.prototype.fillHeight = function (n) {
        var h = FLQ.getAvailHeight(n.parentNode)
        if (h > n.offsetHeight) FLQ.setNodeHeight(n, h)
    }

    /**
     * VerticalBox control
     */
    Milk.Ctrl.VerticalBox = function (id) {
        this.id = id
        this.fitHeight = false
    }

    Milk.Ctrl.VerticalBox.prototype = new MilkCtrl()

    Milk.Ctrl.VerticalBox.prototype.init = function () {
        this.n = _$(this.id)

        var c = this
        if (this.fitHeight) setTimeout(function () { c.fillHeight(c.n); c.fillHeight(c.n.lastChild) }, 100)
    }

    Milk.Ctrl.VertBox = Milk.Ctrl.VerticalBox
    Milk.Ctrl.VBox = Milk.Ctrl.VerticalBox
    Milk.Ctrl.VertContainer = Milk.Ctrl.VerticalBox
    Milk.Ctrl.VertCont = Milk.Ctrl.VerticalBox
    Milk.Ctrl.VCont = Milk.Ctrl.VerticalBox

    /**
     * Form abstrace class
     */
    Milk.Ctrl.Form = function (id) {
        this.id = id
        this.n = null
        this.defaultSend = true
        this.value = null
        this.reqValue = null
    }

    Milk.Ctrl.Form.prototype = new MilkCtrl()

    Milk.Ctrl.Form.prototype.addE = function () {
        if (this.n) {
            var c = this
            FLQ.e.add(this.n, 'focus', function () { FLQ.addClass(c.n.parentNode, 'focussed') })
            FLQ.e.add(this.n, 'blur', function () { FLQ.removeClass(c.n.parentNode, 'focussed') })
        }
    }

    Milk.Ctrl.Form.prototype.getValue = function () {
        return this.n.value
    }

    Milk.Ctrl.Form.prototype.hasChanged = function () {
        return (this.getValue() != this.value ? true : false)
    }

    Milk.Ctrl.Form.prototype.getData = function () {
        if (this.hasChanged()) {
            var d = {}
            d[this.n.name] = this.getValue()
            return d
        }

        return null
    }

    Milk.Ctrl.Form.prototype.setvalue = function (args) {
        var t, v
        if (!FLQ.isSet(typeof args['value']) && FLQ.isSet(typeof args['svr'])) {
            if (args['svr'].responseXML && (t = args['svr'].responseXML.getElementsByTagName('value'))) {
                args['value'] = [t[0].firstChild.nodeValue]
            }
        }

        if (v = Milk.getArg(args, 'value')) {
            this.n.value = v
        }

        this.sendSignal('slotdone')
    }

    Milk.Ctrl.Form.prototype.focus = function () {
        this.n.focus()
    }

    Milk.Ctrl.Form.prototype.hasPlaceholder = function () {
        return 'placeholder' in (document.createElement('input'))
    }

    Milk.Ctrl.Form.prototype.placeholder = function () {
        if (!this.hasPlaceholder() && this.n.getAttribute('placeholder')) {
            var c = this
            FLQ.e.add(this.n, 'focus', function () { if (c.n.value == c.n.getAttribute('placeholder')) { c.n.value = ''; FLQ.removeClass(c.n, 'placeholder') } })
            FLQ.e.add(this.n, 'blur', function () { if (c.n.value == '') { c.n.value = c.n.getAttribute('placeholder'); FLQ.addClass(c.n, 'placeholder') } })
            if (c.n.value == '') { c.n.value = c.n.getAttribute('placeholder'); FLQ.addClass(c.n, 'placeholder') }
        }
    }

