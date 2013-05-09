

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

