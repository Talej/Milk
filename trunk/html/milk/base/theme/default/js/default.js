
    var $ = function (i) { return document.getElementById('mlk-'+i) }

    var Milk = {
        SLOT_SAMEWIN   : '_self',
        SLOT_NEWWIN    : '_blank',
        SLOT_CHILDWIN  : '_child',
        SLOT_MODALWIN  : '_modal',
        SLOT_AJAX      : '_ajax',
        SLOT_LAUNCHER  : '_launcher',
        errors         : [],
        history        : [],
        c              : {},
        NOTIFY_ERROR   : 'error',
        NOTIFY_WARNING : 'warning',
        NOTIFY_MESSAGE : 'message'
    }

    Milk.add = function (ctrl, id, props) {
        // TODO: Check that the control object exists
        // TODO: Check that a control with that id doesn't exist
        var c = new Milk.Ctrl[ctrl](id)
        c.setProps(props)
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
        var args = {}
        for (var i = 0; arguments[i]; i++) {
            if (FLQ.isObj(arguments[i])) {
                for (var j in arguments[i]) {
                    if (!FLQ.isFunc(arguments[i][j])) args[j] = arguments[i][j]
                }
            }
        }

        return args
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

    Milk.notify = function(t, err) {
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
            this.destId == Milk.SLOT_MODALWIN ||
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
            this.dest == Milk.SLOT_MODALWIN ||
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
                if (FLQ.isObj(key)) {
                    for (var i in key) {
                        if (!FLQ.isFunc(key[i])) {
                            add_field(frm, i, key[i])
                        }
                    }
                } else if (FLQ.isObj(val)) {
                    for (var i in val) {
                        if (!FLQ.isFunc(val[i])) {
                            add_field(frm, key+'['+i+']', val[i])
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
            } else if (this.dest == Milk.SLOT_MODALWIN) {
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
                var w = FLQ.modal('/milk/blank.php', props)
                if (!FLQ.isSet(typeof args.height) && w) {
                    FLQ.event.add(w, 'load', function () { setTimeout(HOL.modal.autoHeight, 300) })
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
            if (!FLQ.isSet(typeof args.noact)) {
                add_field(frm, 'act', this.slot)
            } else {
                delete args.noact
            }
            delete args.nohistory
            add_field(frm, args)

            if (this.dest == Milk.SLOT_AJAX) {
                var ajax = new FLQ.ajax(frm.getAttribute('action'))
                ajax.method = 'POST'
                var data = ''
                for (var n,i = 0; frm.childNodes[i]; i++) {
                    n = frm.childNodes[i]
                    if (n.nodeType == 1 && n.nodeName == 'INPUT' && n.getAttribute('type') == 'hidden' && !(/^response\[/.exec(n.name))) {
                        if (data[0]) data += '&'
                        data += escape(n.name)+'='+escape(n.value)
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
            } else {
                // append the form and submit
                document.body.appendChild(frm)
                frm.submit() // Note: Could be a problem with IE requiring setTimeout to be used
            }
        }
    }

    Milk.Conn.ajaxResponse = function (dest, slot, args) {
        var c = new Milk.Conn(null, null, dest, slot, args)
        var f = function (svr) {
            c.exec({'svr':svr, 'validate':false})
            Milk.Conn.ajaxShowErrors(svr)
        }

        return f
    }

    Milk.Conn.ajaxShowErrors = function (svr) {
        if (svr.responseXML) {
            var e = svr.responseXML.getElementsByTagName('error')
            var s = []
            for (var i=0; e[i]; i++) {
                if (e[i].firstChild.nodeType == 3) {
                    s.push(e[i].firstChild.nodeValue)
                }
            }
            if (s[0]) {
                alert('- '+s.join('\n- '))
            }
        }
    }

    Milk.Conn.ajaxUnauthorised = function () {
        new Milk.Conn(null, null, Milk.SLOT_SAMEWIN, 'refresh').exec({'send':false, 'validate':false})
    }

    var MilkCtrl = function () {
        this.id      = null
        this.signals = null
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
        } else if (!this.strictConns) {
            this.sendSignal(slot, args, e)
        } else {
            throw('Unable to execute slot '+slot)
        }
    }

    MilkCtrl.prototype.fillHeight = function (n) {
        var h = FLQ.getAvailHeight(n.parentNode)
        if (h > n.offsetHeight) FLQ.setNodeHeight(n, h)
    }

    /**
     * Text control
     */
    Milk.Ctrl.Text = function (id) {
        this.id = id
        this.n  = null
    }

    Milk.Ctrl.Text.prototype = new MilkCtrl()

    Milk.Ctrl.Text.prototype.init = function () {
        this.n = $(this.id)
        var c = this
        FLQ.event.add(this.n, 'click', function (e) { c.sendSignal('click'); FLQ.event.stopEvent(e) })
    }

    /**
     * Label control
     */
    Milk.Ctrl.Label = function (id) {
        this.id = id
        this.n  = null
    }

    Milk.Ctrl.Label.prototype = new MilkCtrl()

    Milk.Ctrl.Label.prototype.init = function () {
        this.n = $(this.id)
        var c = this
        FLQ.event.add(this.n, 'click', function (e) { c.sendSignal('click'); FLQ.event.stopEvent(e) })
    }

    /**
     * Heading control
     */
    Milk.Ctrl.Heading = function (id) {
        this.id = id
        this.n  = null
    }

    Milk.Ctrl.Heading.prototype = new MilkCtrl()

    Milk.Ctrl.Heading.prototype.init = function () {
        this.n = $(this.id)
        var c = this
        FLQ.event.add(this.n, 'click', function (e) { c.sendSignal('click'); FLQ.event.stopEvent(e) })
    }

    /**
     * Image control
     */
    Milk.Ctrl.Image = function (id) {
        this.id = id
        this.n  = null
    }

    Milk.Ctrl.Image.prototype = new MilkCtrl()

    Milk.Ctrl.Image.prototype.init = function () {
        this.n = $(this.id)

        var c = this
        FLQ.event.add(this.n, 'click', function () { c.sendSignal('click') })
    }

    /**
     * Terminator control
     */
    Milk.Ctrl.Terminator = function (id) {
        this.id = id
    }

    Milk.Ctrl.Terminator.prototype = new MilkCtrl()

    /**
     * VerticalBox control
     */
    Milk.Ctrl.VerticalBox = function (id) {
        this.id = id
        this.fitHeight = false
    }

    Milk.Ctrl.VerticalBox.prototype = new MilkCtrl()

    Milk.Ctrl.VerticalBox.prototype.init = function () {
        this.n = $(this.id)

        var c = this
        if (this.fitHeight) setTimeout(function () { c.fillHeight(c.n); c.fillHeight(c.n.lastChild) }, 100)
    }

    /**
     * Template control
     */
    Milk.Ctrl.Template = function (id) {
        this.id = id
    }

    Milk.Ctrl.Template.prototype = new MilkCtrl()

    Milk.Ctrl.Template.prototype.init = function () {
        setTimeout('Milk.get(\''+this.id+'\').load()', 0);
    }

    Milk.Ctrl.Template.prototype.load = function () {
        this.sendSignal('load', {'send': false})
    }

    /**
     * Tabs control
     */
    Milk.Ctrl.Tabs = function (id) {
        this.id = id
    }

    Milk.Ctrl.Tabs.prototype = new MilkCtrl()

    /**
     * ListView control
     */
    Milk.Ctrl.DataGrid = function (id) {
        this.id              = id
        this.defaultRequired = true
        this.n               = null
        this.b               = null
        this.f               = []
        this.perpage         = null
        this.offset          = null
        this.totalrows       = null
    }

    Milk.Ctrl.DataGrid.prototype = new MilkCtrl()

    Milk.Ctrl.DataGrid.prototype.init = function () {
        this.n = $(this.id)
        this.b = this.n.tBodies[0]

        var c = this
        var tr = this.n.getElementsByTagName('TR')
        for (var i=1; tr[i]; i++) {
            FLQ.event.add(tr[i], 'mouseover', function () { FLQ.addClass(this, 'datagrid-hover'); c.sendSignal('hover') })
            FLQ.event.add(tr[i], 'mouseout', function () { FLQ.removeClass(this, 'datagrid-hover') })
            FLQ.event.add(tr[i], 'click', function (e) { c.focus(this.rowIndex, e) })
            FLQ.event.add(tr[i], 'dblclick', function (e) { c.select(this.rowIndex, e) })
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
        if (e.ctrlKey) {
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

    /**
     * Button control
     */
    Milk.Ctrl.Button = function (id) {
        this.id = id
        this.n  = null
    }

    Milk.Ctrl.Button.prototype = new MilkCtrl()

    Milk.Ctrl.Button.prototype.init = function () {
        this.n = $(this.id)
        var c = this
        FLQ.event.add(this.n, 'click', function (e) { c.sendSignal('click'); FLQ.event.stopEvent(e) })
    }

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
        var v
        if (v = Milk.getArg(args, 'value')) {
            this.n.value = v
        }
    }

    Milk.Ctrl.Form.prototype.focus = function () {
        this.n.focus()
    }

    /**
     * TextBox control
     */
    Milk.Ctrl.TextBox = function (id) {
        this.id = id
        this.n  = null
    }

    Milk.Ctrl.TextBox.prototype = new Milk.Ctrl.Form()

    Milk.Ctrl.TextBox.prototype.init = function () {
        this.n = $(this.id).firstChild

        var c = this
        FLQ.event.add(this.n, 'keypress', function (e) { c.keypress(e) })
    }

    Milk.Ctrl.TextBox.prototype.keypress = function (e) {
        if (e.keyCode && e.keyCode == 13) {
            if (this.n.nodeName.toLowerCase() != 'textarea') {
                this.sendSignal('enter')
                FLQ.event.stopEvent(e)
            }
        }
    }

    /**
     * PasswordBox control
     */
    Milk.Ctrl.PasswordBox = function (id) {
        this.id = id
    }

    Milk.Ctrl.PasswordBox.prototype = new Milk.Ctrl.TextBox()

    Milk.Ctrl.PasswordBox.prototype.init = function () {
        this.n = $(this.id).firstChild

        var c = this
        FLQ.event.add(this.n, 'keypress', function (e) { c.keypress(e) })
    }

    /**
     * ListBox control
     */
    Milk.Ctrl.ListBox = function (id) {
        this.id  = id
        this.min = null
        this.max = 1
    }

    Milk.Ctrl.ListBox.prototype = new Milk.Ctrl.Form()

    Milk.Ctrl.ListBox.prototype.init = function () {
        this.n = $(this.id).firstChild
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
                return this.n.options[this.n.selectedIndex].value
            } else {
                return null
            }
        }
    }

    /**
     * Boolean control
     */
    Milk.Ctrl.BoolBox = function (id) {
        this.id = id
        this.n = null
    }

    Milk.Ctrl.BoolBox.prototype = new Milk.Ctrl.Form()

    Milk.Ctrl.BoolBox.prototype.init = function () {
        this.n = $(this.id).firstChild
    }

    Milk.Ctrl.BoolBox.prototype.getValue = function () {
        return (this.n.checked ? 1 : 0)
    }

    Milk.Ctrl.BoolBox.prototype.setvalue = function (args) {
        var v = (Milk.getArg(args, 'value') ? true : false)
        this.n.checked = v
        this.value = v
    }

    Milk.Ctrl.BoolBox.prototype.toggle = function () {
        this.setvalue({'value':(this.getValue() ? false : true)})
    }

    /**
     * ChooseBox control
     */
    Milk.Ctrl.ChooseBox = function (id) {
        this.id = id
    }

    Milk.Ctrl.ChooseBox.prototype = new Milk.Ctrl.Form()

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
        this.p = $(this.id)
        this.n = this.p.firstChild

        var c = this
        FLQ.event.add(this.n, 'focus', function () { c.show() })
        FLQ.event.add(this.n, 'click', function (e) { FLQ.event.stopEvent(e) })
        FLQ.event.add(this.n, 'change', function () { c.hide() })
        FLQ.event.add(this.n, 'keypress', function (e) { c.keypress(e) })
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
        FLQ.event.add(document.body, 'click', function () { setTimeout('Milk.get(\''+c.id+'\').hide()', 100) })

        // calendar nav & month label
        b.appendChild(tr = document.createElement('tr'))
        tr.appendChild(td = document.createElement('td'))
        FLQ.addClass(tr, 'datebox-title');
        td.appendChild(document.createTextNode('«'))
        FLQ.addClass(td, 'datebox-prev');
        FLQ.event.add(td, 'click', function (e) { c.show((m == 0 ? 11 : m-1), (m == 0 ? y-1 : y)); FLQ.event.stopEvent(e) })
        tr.appendChild(td = document.createElement('td'))
        td.appendChild(document.createTextNode(v.mths[m]+' '+y))
        td.colSpan = 5;
        tr.appendChild(td = document.createElement('td'))
        td.appendChild(document.createTextNode('»'))
        FLQ.addClass(td, 'datebox-next');
        FLQ.event.add(td, 'click', function (e) { c.show((m == 11 ? 0 : m+1), (m == 11 ? y+1 : y));  FLQ.event.stopEvent(e) })

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
                FLQ.event.add(td, 'click', new Function('', 'with (ctr = Milk.get(\''+c.id+'\')) { setvalue({\'value\': getCalValue(\''+dt.strftime(c.fmt)+'\')}) }'))
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
            FLQ.event.add(h, 'click', function (e) { FLQ.event.stopEvent(e); })
            t.appendChild(mi = document.createElement('select'))
            o = 0
            for (var i=0; i < 60; i++) {
                mi.options[o++] = new Option((i < 10 ? '0' : '')+i, i, (v.getMinutes() == i ? true : false))
            }
            FLQ.event.add(mi, 'click', function (e) { FLQ.event.stopEvent(e); })
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
        FLQ.event.removeListener(document.body, 'click', function () { setTimeout('Milk.get(\''+c.id+'\').hide()', 100) })
    }

    /**
     * DateTimeBox control
     */
    Milk.Ctrl.DateTimeBox = function (id) {
        this.id       = id
        this.p        = null
        this.fmt      = '%d/%m/%Y %H:%M'
        this.showtime = true
    }

    Milk.Ctrl.DateTimeBox.prototype = new Milk.Ctrl.DateBox()

    Milk.Ctrl.DateTimeBox.prototype.init = function () {
        this.p = $(this.id)
        this.n = this.p.firstChild

        var c = this
        FLQ.event.add(this.n, 'focus', function () { c.show() })
        FLQ.event.add(this.n, 'click', function (e) { FLQ.event.stopEvent(e) })
        FLQ.event.add(this.n, 'change', function () { c.hide() })
        FLQ.event.add(this.n, 'keypress', function (e) { c.keypress(e) })
    }

    /**
     * FileBox control
     */
    Milk.Ctrl.FileBox = function (id) {
        this.id      = id
        this.n       = null
        this.changed = false
    }

    Milk.Ctrl.FileBox.prototype = new Milk.Ctrl.Form()

    Milk.Ctrl.FileBox.prototype.init = function () {
        this.n = $(this.id).firstChild

        var c = this
        FLQ.event.add(this.n, 'change', function () { c.changed = true })
    }

    Milk.Ctrl.FileBox.prototype.hasChanged = function () {
        return this.changed
    }

    Milk.Ctrl.FileBox.prototype.addData = function (f) {
        if (this.hasChanged()) {
            f.encoding = 'multipart/form-data'
            this.n.style.display = 'none'
            f.appendChild(this.n)
        }
    }
