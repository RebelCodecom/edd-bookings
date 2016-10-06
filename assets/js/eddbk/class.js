/* global top */
/* global EddBk */

// Based on John Resig's Class pattern
;(function () {
    EddBk = top.EddBk || {};
    var initializing = false,
        fnTest = /xyz/.test(function () {
            xyz;
        }) ? /\b_super\b/ : /.*/;
    
    EddBk.Class = function () {
    };
    
    EddBk.Class.extend = function (prop) {
        var _super = this.prototype;
        initializing = true;
        var prototype = new this();
        initializing = false;
        for (var name in prop) {
            prototype[name] = (typeof prop[name] === "function" && typeof _super[name] === "function" && fnTest.test(prop[name]))
                ? (function (name, fn) {
                    return function () {
                        var tmp = this._super;
                        this._super = _super[name];
                        var ret = fn.apply(this, arguments);
                        this._super = tmp;
                        return ret;
                    };
                })(name, prop[name])
                : prop[name];
        }
        function Class() {
            if (!initializing && this.init) {
                this.init.apply(this, arguments);
            }
        }
        Class.prototype = prototype;
        Class.prototype._super = _super;
        Class.prototype.constructor = Class;
        Class.extend = arguments.callee;
        return Class;
    };
})();

// A customization of the Class pattern. Allows structured Mixins
;(function (Class) {
    Class.augment = function (destination, source) {
        for (var prop in source) {
            if (!source.hasOwnProperty(prop)) {
                continue;
            }
            destination.prototype[prop] = typeof (destination.prototype[prop]) !== 'undefined'
                ? (function (prop) {
                        var fn = destination.prototype[prop];
                        return function () {
                            // Save any _super variable that already existed
                            var tmp = this._super;

                            this._super = source[prop];
                            fn.apply(this, arguments);

                            // Restore _super
                            this._super = tmp;
                        };
                })(prop)
                : source[prop];
        }

        return destination;
    };
})(EddBk.Class);
