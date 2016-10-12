/* global EddBk, top */

;(function ($, undefined) {

    EddBk.newClass('EddBk.Widget.SessionPicker', EddBk.Widget, {
        /**
         * Constructor.
         *
         * @param {$|Element} element
         * @param {object} options
         */
        init: function (element, options) {
            this._super(element, 'Widget.SessionPicker');
            this.addData($.extend(this.getDefaultOptions(), options));
            this.widgets = {};
            this.widgetsLoaded = 0;
            this.localTzOffset = (new Date()).getTimezoneOffset() * -60;
        },
        /**
         * Default options.
         *
         * @returns {object}
         */
        getDefaultOptions: function() {
            return {
                availability: new EddBk.Availability.Controller.RegistryController(),
                unit: EddBk.Utils.Units.hours,
                sessionLength: 3600,
                minSessions: 1,
                maxSessions: 1,
                stepSessions: 1,
                sessionCost: 0
            };
        },

        /**
         * Triggered when the widget content has been loaded from AJAX.
         */
        onContentLoaded: function() {
            this.initElements();
            this.initEvents();
        },

        /**
         * Initializes the elements and sets the pointers in data
         */
        initElements: function() {
            this.widgetsLoaded = 0;
            this.widgets = {
                datePicker: new EddBk.Widget.DatePicker(this.find('.edd-bk-date-picker-widget')),
                timePicker: new EddBk.Widget.TimePicker(this.find('.edd-bk-time-picker-widget')),
                durationPicker: new EddBk.Widget.DurationPicker(this.find('.edd-bk-duration-picker-widget'))
            };
            this.sessionOptionsElem = this.find('.edd-bk-session-options');
        },
        /**
         * Initializes the events.
         */
        initEvents: function() {
            this.getDatePicker().loadContent(this.onChildWidgetLoaded.bind(this));
            this.getTimePicker().loadContent(this.onChildWidgetLoaded.bind(this));
            this.getDurationPicker().loadContent(this.onChildWidgetLoaded.bind(this));
        },
        /**
         * Triggered when a child widget has been loaded
         */
        onChildWidgetLoaded: function() {
            // Update `loaded` data
            this.widgetsLoaded++;
            // Check if all child widgets have been loaded
            if (this.widgetsLoaded >= Object.keys(this.getWidgets()).length) {
                this.onLoaded();
                this.trigger('loaded');
            }
        },
        /**
         * Triggered when all child widgets have been loaded.
         */
        onLoaded: function () {
            this.update();
        },

        /**
         * Updates the widget and all child widgets.
         */
        update: function() {
            this.updateDatePicker();
            this.updateTimePicker();
            this.updateDurationPicker();
        },
        /**
         * Updates the datepicker.
         */
        updateDatePicker: function() {
            this.getDatePicker().beforeShowDay = this.isDateAvailable.bind(this);
            this.getDatePicker().onDateSelected = this.onDateSelected.bind(this);
            this.getDatePicker().onChangeMonthYear = this.onChangeMonthYear.bind(this);
            this.getDatePicker().update();
        },
        /**
         * Updates the time picker.
         */
        updateTimePicker: function() {
            var timeUnit = EddBk.Utils.isTimeUnit(this.getData('unit'));
            // If using a time unit, update the timepicker
            if (timeUnit) this.getTimePicker().update();
            // Show if using a time unit, hide otherwise
            this.getTimePicker().l.toggle(timeUnit);
        },
        /**
         * Updates the duration picker.
         */
        updateDurationPicker: function() {
            this.getDurationPicker().addData({
                unit: this.getData('unit'),
                min: this.getData('minSessions'),
                max: this.getData('maxSessions'),
                step: this.getData('stepSessions')
            });
            this.getDurationPicker().update();
        },

        /**
         * Checks if a date is available.
         *
         * @param {Date} date The date object.
         * @returns {boolean}
         */
        isDateAvailable: function(date) {
            return this.getAvailability().hasSessions(date);
        },

        /**
         * @override
         * Triggered on date selection. Updates the timepicker with the sessions for the selected date.
         */
        onDateSelected: function(date) {
            var sessions = this.getAvailability().getSessions(date);
            this.getTimePicker().setData('times', sessions);
            this.updateTimePicker();
            this.toggleSessionOptions(true);
        },

        /**
         * Toggles the visibility of the session options container.
         *
         * @param {boolean} toggle
         */
        toggleSessionOptions: function(toggle) {
            this.sessionOptionsElem.toggle(toggle);
        },

        /**
         * @override
         */
        onChangeMonthYear: function(year, month) {},

        /**
         * Gets the widgets.
         *
         * @returns {Array}
         */
        getWidgets: function() {
            return this.widgets;
        },
        /**
         * Gets the date picker widget instance.
         *
         * @returns {EddBk.Widget.DatePicker}
         */
        getDatePicker: function() {
            return this.getWidgets().datePicker;
        },
        /**
         * Gets the timer picker widget instance.
         *
         * @returns {EddBk.Widget.TimePicker}
         */
        getTimePicker: function() {
            return this.getWidgets().timePicker;
        },
        /**
         * Gets the duration picker widget instance.
         *
         * @returns {EddBk.Widget.DurationPicker}
         */
        getDurationPicker: function() {
            return this.getWidgets().durationPicker;
        },

        /**
         * Gets the availability controller.
         *
         * @returns {EddBk.Availability.Controller}
         */
        getAvailability: function() {
            return this.getData('availability');
        }
    });

})(jQuery);
