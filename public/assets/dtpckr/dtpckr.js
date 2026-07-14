/**
 * ## dtpckr 0.0.3 ##
 */

(function (factory) {
	"use strict";
	if (typeof define === 'function' && define.amd) {
		define(['jquery'], factory);
	}
	else if(typeof module !== 'undefined' && module.exports) {
		module.exports = factory(require('jquery'));
	}
	else {
		factory(jQuery);
	}
}(function ($, undefined) {
	"use strict";

(function ($, undefined) {
	"use strict";

	// prevent another load? maybe there is a better way?
	//if($.dtpckr) { return; }

	// internal variables
	var instance_counter = 0;

	/**
	 * ### dtpckr settings
	 *
	 * `$.dtpckr.defaults` stores all defaults for the fleetmatics plugin.
	 *
	 * * `mode` determines the mode of the picker, possible values are _date_, _datetime_, _range_, _rangetime_, _multiple_. Default is _date_
	 * * `time` controls various time settings if the tree is in _datetime_ or _rangetime_ mode
	 *	* `style` determines the control to use for time selection, possible values are _input_, _inputs_, _select_, _selects_, _combo_, _combos_ default is _false_ which disabled time
	 *	* `hours` how to pick hours: _true_ means 0-23, an array of values can be passed _[0,12,23]_ for select & combo boxes, or a step value as an integer _2_. _false_ disables the hour picker.
	 *	* `minutes` how to pick minutes: _true_ means 0-59, an array of values can be passed _[0,12,23]_ for select & combo boxes, or a step value as an integer _2_. _false_ disables the minute picker.
	 *	* `seconds` how to pick seconds: _true_ means 0-59, an array of values can be passed _[0,12,23]_ for select & combo boxes, or a step value as an integer _2_. _false_ disables the minute picker.
	 * * `months` the number of months to display side by side, default is _1_
	 * * `value` the value to populate the picker with (if an input is used its value will be used autmatically)
	 * * `showWeek` toggles the week numbers, default is _true_
	 * * `minDate` the minimum date to be selectable - can be a date object, a string (which MUST be in the _localize.format_) or a relative value '-1 month'
	 * * `maxDate` the maximum date to be selectable - can be a date object, a string (which MUST be in the _localize.format_) or a relative value '+1 month'
	 * * `disabled` should the control be disabled
	 * * `localize` object of localization options
	 *	* `monthNames` an array of month names (strings)
	 *	* `monthNamesShort` an array of short month names (strings) (Jan, Feb, ...)
	 *	* `dayNames` an array of day names (strings)
	 *	* `dayNamesShort` an array of short day names (strings) (Mon, Tue ...)
	 *	* `dateFormat` the format to parse and display the date in. Default is dd.mm.yy (which means 30.05.2013 for example)
	 *	* `firstDay` which day starts the week (default is 1 - for Monday)
	 * * `style` _inline_ or _popup_, default is inline, if an input is used, _popup_ is selected automatically
	 * * `width` the width of a single month in _popup_ mode
	 * * `change` a functions to be called whenever the value changes (inputs are updated automatically)
	 * * `render` a function that receives a date, and should return an array of classes (so that you can style specific dates like national holidays)
	 * * `striped` should the odd rows have a different color
	 * * `showPrevNext` controls whether days from previous and next month are shown
	 * * `rangeTitleSelectsMonth` if set to true, clicking on the month title in range mode selects the whole month
	 */
	$.dtpckr = {
		version : '0.0.3',
		defaults : {
			mode		: 'date',  // date / datetime / range / rangetime / multiple
			time		: {
				hours				: true,		// true, false, array of values
				minutes				: true,
				seconds				: false,
				ampm				: false,
				combo				: false,
				restrict			: false
			},
			trigger		: null,
			closeChange	: null,
			months		: 1,
			value		: null,
			showWeek	: true,
			minDate		: null,
			maxDate		: null,
			disabled	: false,
			localize	: 'bg',
			style		: 'inline', // popup or inline (popup is autoselected for inputs)
			width		: 250,
			change		: $.noop,
			render		: $.noop,
			hide		: $.noop,
			striped		: true,
			showPrevNext: false,
			rangeWeekSelectsWeek : false,
			rangeTitleSelectsMonth : false,
			clearButton : false,
			persist : false
		},
		localizations	: {
			'en' : {
				monthNames:			["January","February","March","April","May","June","July","August","September","October","November","December"],
				monthNamesShort:	["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
				dayNames:			["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
				dayNamesShort:		["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
				dateFormat:			'dd.mm.yy',
				firstDay:			1,
				clear:				'Clear'
			},
			'bg' : {
				monthNames:			['Януари','Февруари','Март','Април','Май','Юни','Юли','Август','Септември','Октомври','Ноември','Декември'],
				monthNamesShort:	['Яну','Фев','Мар','Апр','Май','Юни','Юли','Авг','Сеп','Окт','Ное','Дек'],
				dayNames:			['Неделя','Понеделник','Вторник','Сряда','Четвъртък','Петък','Събота'],
				dayNamesShort:		['Нед','Пон','Вто','Сря','Чет','Пет','Съб'],
				dateFormat:			'dd.mm.yy',
				firstDay:			1,
				clear:				'Изчисти'
			}
		},
		create : function (el, options) {
			el = $(el);
			options = options || {};
			options = $.extend(true, {}, $.dtpckr.defaults, options);
			if(!$.isPlainObject(options.localize)) {
				options.localize = $.dtpckr.localizations[options.localize] || $.dtpckr.localizations.en;
			}
			else {
				options.localize = $.extend({}, $.dtpckr.localizations.en, options.localize);
			}
			var format = options.localize.dateFormat, i, j, v, val, to, tmp;
			if(options.mode.indexOf('time') !== -1 && options.time) {
				format += ' ';
				if(options.time.hours)   { format += 'HH'; }
				if(options.time.minutes) { format += ':ii'; }
				if(options.time.seconds) { format += ':ss'; }
			}
			//console.log(options.value);
			//options.value = $.dtpckr.determineDate(format, options.value, options.localize) || null;
			//console.log(options.value);
			if(el.is('input')) {
				if(!options.value) {
					options.value = el.val().toString() || null;
				}
				if (el.attr('type') === 'hidden') {
					options.style = 'inline';
					el.after('<div>');
					el = el.next();
					if (!options.change || options.change === $.noop) {
						options.change = function (v, formatted) {
							el.prev().val(formatted);
						};
					}
				} else {
					el.css('-webkit-tap-highlight-color', 'rgba(0,0,0,0)');
					if(navigator && navigator.userAgent && /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
						el.prop('readOnly', true);
					}
					options.style = 'popup';
				}
			}
			if(options.value && typeof options.value === "string") {
				switch(options.mode) {
					case 'range':
					case 'rangetime':
						options.value = options.value.split(' - ');
						for(i = 0, j = options.value.length; i < j; i++) {
							options.value[i] = $.dtpckr.determineDate(format, options.value[i], options.localize) || null;
						}
						break;
					case 'multiple':
						options.value = options.value.split(', ');
						for(i = 0, j = options.value.length; i < j; i++) {
							options.value[i] = $.dtpckr.determineDate(format, options.value[i], options.localize) || null;
						}
						break;
					/*
					case 'date':
					case 'datetime':
					*/
					default:
						options.value = $.dtpckr.determineDate(format, options.value, options.localize) || null;
						break;
				}
			}
			if(options.value) {
				v = null;
				val = options.value;
				switch(options.mode) {
					case 'range':
					case 'rangetime':
						v = [].concat(val);
						$.each(v, $.proxy(function (i, vv) {
							v[i] = $.dtpckr.formatDate(format, vv, options.localize);
						}, this));
						v = v.join(' - ');
						break;
					case 'multiple':
						v = [].concat(val);
						$.each(v, $.proxy(function (i, vv) {
							v[i] = $.dtpckr.formatDate(format, vv, options.localize);
						}, this));
						v = v.join(', ');
						break;
					case 'date':
					case 'datetime':
						v = $.dtpckr.formatDate(format, val, options.localize);
						break;
				}
				if(v) {
					el.val(v);
				}
			}

			if(el.is('input')) {
				to = false;

				el.keydown(function (e, data) {
					if(to) { clearTimeout(to); }
					to = setTimeout($.proxy(function () {
						if($(this).dtpckr('is_disabled')) {
							$(this).val($(this).dtpckr('get', true));
						}
						else {
							var vv = $(this).val(),
								u = true;
							// if date is not valid - restore last date
							if(vv === '') {
								$(this).dtpckr('set', null, false, u, true);
							}
							else {
								try {
									$.dtpckr.parseDate(format, vv, options.localize);
								}
								catch (e) {
									vv = $(this).dtpckr('get', true);
									u = false;
								}
								$(this).dtpckr('set', vv, false, u, true);
							}
						}
					}, this), 500);
				});
			}
			if(options.closeChange === null && options.style === 'popup' && options.mode === 'date') {
				options.closeChange = true;
			}
			tmp = new $.dtpckr.instance(++instance_counter, el, options);
			$(el).data('dtpckr', tmp);
			$(tmp.control).data('dtpckr', tmp); // bad?
			return tmp;
		},
		instance : function (id, el, options) {
			this._id = id;
			this.element = el;
			this.settings = options;
			this.format = this.settings.localize.dateFormat;
			var tmp, tf;
			if(this.settings.mode.indexOf('time') !== -1 && this.settings.time) {
				tf = '';
				if(this.settings.time.style && this.settings.time.style === 'combo') {
					this.settings.time.combo = true;
				}
				if(this.settings.time.hours) {   tf += 'HH'; }
				if(this.settings.time.minutes) { tf += ':ii'; }
				if(this.settings.time.seconds) { tf += ':ss'; }
				this.format += ' ' + tf;
			}
			this.settings.localize.ampm = this.settings.time.ampm;

			this.minDate = this.settings.minDate ? $.dtpckr.determineDate(this.format, this.settings.minDate, this.settings.localize, null) : null;
			this.maxDate = this.settings.maxDate ? $.dtpckr.determineDate(this.format, this.settings.maxDate, this.settings.localize, null) : null;

			this.view = 'dates';

			this.control = $('<div class="jquery-dtpckr">'+
					'<div class="jquery-dtpckr-navigation">'+
						'<a href="#" class="jquery-dtpckr-prev">&laquo;</a>'+
						'<a href="#" class="jquery-dtpckr-next">&raquo;</a>'+
						'<a href="#" class="jquery-dtpckr-current"></a>'+
					'</div>'+
					'<table class="jquery-dtpckr-table jquery-dtpckr-table-main '+(this.settings.showPrevNext?'':'jquery-dtpckr-no-prev jquery-dtpckr-no-next')+' '+(this.settings.striped?'jquery-dtpckr-striped':'')+' '+(this.settings.showWeek?'jquery-dtpckr-with-week':'')+'" cellpadding="0" cellspacing="0">'+
					'</table>'+
				'</div>');
			if(this.settings.style === 'popup') {
				this.control.width(this.settings.width * this.settings.months);
				this.control.addClass('jquery-dtpckr-popup');
			}
			this.control.appendTo(this.settings.style === 'inline' ? this.element : document.body);

			if(this.settings.mode.indexOf('time') !== -1 && this.settings.time) {
				tmp  = '<div class="jquery-dtpckr-time">';
				tmp += '<input type="text" name="time" class="jquery-dtpckr-time-single" value="" />';
				tmp += '</div>';
				this.control.append(tmp);
				if(this.settings.mode === 'rangetime') {
					this.control.append(tmp).children('.jquery-dtpckr-time').width('49%').css('display','inline-block');
				}

				if(this.settings.style === 'popup') {
					this.control.css({'visibility':'hidden','display':'block'});
				}
				this.control.find('.jquery-dtpckr-time-single').each($.proxy(function (i,v) {
					$(v).tmpckr(this.settings.time);
				}, this));

				this.control.find('.jquery-dtpckr-combo').each(function () {
					var t = $(this),
						w = t.width(),
						h = t.height(),
						n = t.attr('name'),
						ttmp = $('<input type="text" name="'+n+'" />').css({ position: 'relative', 'margin-right': '20px', 'margin-left': '-'+(w-1)+'px', 'width': (w-25)+'px', 'height': (h-2)+'px', 'border':0})
					t
						.change(function () {
							$(this).next().val($(this).find(':selected').text()).select().change();
						})
						.attr('name','')
						.after(ttmp)
						.change()
						.next()
							.on('keydown', function (e) {
								var isNumeric = (e.which >= 48 && e.which <= 57) || (e.which >= 96 && e.which <= 105),
									direction = (e.which === 8 || e.which === 37 || e.which === 38 || e.which === 39 || e.which === 40 || e.which === 46),
									iscolon = e.shiftKey && e.which === 59;
								if(!isNumeric && !direction && !iscolon) { // && !modifiers
									e.preventDefault();
									return false;
								}
							});
				});
				this.control.find('.jquery-dtpckr-time').on('change', 'select, input', $.proxy(function (e) {
					var t = $(e.target),
						v = t.val(),
						c = t.parent(),
						i = this.control.find('.jquery-dtpckr-time').index(c),
						h = 0,
						m = 0,
						s = 0;
					v = $.tmpckr.parse(this.settings.time, v);
					if(this.settings.time.hours)   { h = v.getHours(); }
					if(this.settings.time.minutes) { m = v.getMinutes(); }
					if(this.settings.time.seconds) { s = v.getSeconds(); }

					h = parseInt(h,10);
					m = parseInt(m,10);
					s = parseInt(s,10);
					if(!h || h < 0 || h > 23) { h = 0; }
					if(!m || m < 0 || m > 59) { m = 0; }
					if(!s || s < 0 || h > 59) { s = 0; }
					switch(this.settings.mode) {
						case 'datetime':
							if(this.selected) {
								this.selected.setHours(h);
								this.selected.setMinutes(m);
								this.selected.setSeconds(s);
							}
							break;
						case 'rangetime':
							if(this.selected && this.selected[i]) {
								this.selected[i].setHours(h);
								this.selected[i].setMinutes(m);
								this.selected[i].setSeconds(s);
							}
							break;
					}
					this.dates();
					this._trigger_change();
				}, this));
			}

			if(this.settings.clearButton) {
				tmp  = '<div class="jquery-dtpckr-clear">';
				tmp += '<a href="#">' + this.settings.localize.clear + '</a>';
				tmp += '</div>';
				this.control.append(tmp);
				this.control.on('click', '.jquery-dtpckr-clear a', $.proxy(function (e) {
					e.preventDefault();
					this.set(null);
				}, this));
			}

			this.set(this.settings.value, true, true, true);

			this.dates();

			if(this.settings.style === 'popup') {
				this.control.css({'visibility':'visible','display':'none'});
				//this.control.find('.jquery-dtpckr-time-single').tmpckr('hide_select');
			}
			else {
				this.control.find('.jquery-dtpckr-time').find('input').each(function () { $(this).tmpckr('position_select'); });
			}

			this.disabled = false;
			if(this.settings.disabled) { this.disable(); }

			if(this.settings.style === 'popup') {
				this.element
					.on('focus click', $.proxy(function (e) {
							this.show();
						}, this));
			}
			this.control
				.on('click', function (e) {
						e.preventDefault();
						e.stopImmediatePropagation();
					})
				.on('click', '.jquery-dtpckr-prev', $.proxy(function (e) {
						e.preventDefault();
						if($(e.currentTarget).hasClass('.jquery-dtpckr-item-disabled')) {
							return false;
						}
						this.prev();
					}, this))
				.on('click', '.jquery-dtpckr-next', $.proxy(function (e) {
						e.preventDefault();
						if($(e.currentTarget).hasClass('.jquery-dtpckr-item-disabled')) {
							return false;
						}
						this.next();
					}, this))
				.on('click', '.jquery-dtpckr-week', $.proxy(function (e) {
						var t, c, y, m;
						if(this.settings.mode === 'range' && this.settings.rangeWeekSelectsWeek && !this.disabled) {
							t = $(e.target).closest('table');
							c = $(e.target).closest('tr').find('td.jquery-dtpckr-item').not('.jquery-dtpckr-item-disabled');
							y = t.data('y');
							m = t.data('m');
							this.selected[0] = new Date(y,m - (c.eq(0).hasClass('jquery-dtpckr-prev-month') ? 1 : 0),c.eq(0).text());
							this.selected[1] = new Date(y,m + (c.eq(-1).hasClass('jquery-dtpckr-next-month') ? 1 : 0),c.eq(-1).text());
							this.dates();
							this._trigger_change();
						}
						if(this.settings.mode === 'rangetime' && this.settings.rangeWeekSelectsWeek && !this.disabled) {
							t = $(e.target).closest('table');
							c = $(e.target).closest('tr').find('td.jquery-dtpckr-item').not('.jquery-dtpckr-item-disabled');
							y = t.data('y');
							m = t.data('m');
							this.selected[0] = new Date(y,m - (c.eq(0).hasClass('jquery-dtpckr-prev-month') ? 1 : 0),c.eq(0).text(),0,0,0);
							this.selected[1] = new Date(y,m + (c.eq(-1).hasClass('jquery-dtpckr-next-month') ? 1 : 0),c.eq(-1).text(),23,this.settings.time.minutes?59:0,this.settings.time.seconds?59:0);
							this.dates();
							this._trigger_change();
						}
					}, this))
				.on('click', '.jquery-dtpckr-current', $.proxy(function (e) {
						e.preventDefault();
						var t, i, c, y, m;
						switch(this.view) {
							case 'dates':
								if(this.settings.mode === 'range' && this.settings.rangeTitleSelectsMonth && !this.disabled) {
									i = $(e.target).is('span') ? $(e.target).index() : 0;
									t = this.control.children('table').eq(i);
									c = t.find('td.jquery-dtpckr-item').not('.jquery-dtpckr-prev-month, .jquery-dtpckr-next-month, .jquery-dtpckr-item-disabled');
									y = t.data('y');
									m = t.data('m');
									this.selected[0] = new Date(y,m,c.eq(0).text());
									this.selected[1] = new Date(y,m,c.eq(-1).text());
									this.dates();
									this._trigger_change();
								}
								else if(this.settings.mode === 'rangetime' && this.settings.rangeTitleSelectsMonth && !this.disabled) {
									i = $(e.target).is('span') ? $(e.target).index() : 0;
									t = this.control.children('table').eq(i);
									c = t.find('td.jquery-dtpckr-item').not('.jquery-dtpckr-prev-month, .jquery-dtpckr-next-month, .jquery-dtpckr-item-disabled');
									y = t.data('y');
									m = t.data('m');
									this.selected[0] = new Date(y,m,c.eq(0).text(),0,0,0);
									this.selected[1] = new Date(y,m,c.eq(-1).text(),23,this.settings.time.minutes?59:0,this.settings.time.seconds?59:0);
									this.dates();
									this._trigger_change();
								}
								else {
									this.months();
								}
								break;
							case 'months':
								this.years();
								break;
						}
					}, this))
				.on('click', '.jquery-dtpckr-item', $.proxy(function (e) {
						var t = $(e.currentTarget),
							c = t.closest('table'),
							y = c.data('y'),
							m = c.data('m'),
							d = t.text(),
							v = null,
							tmp1, h = 0, i = 0, s = 0, h1 = 0, i1 = 0, s1 = 0;
						if(t.hasClass('jquery-dtpckr-prev-month')) { m --; }
						if(t.hasClass('jquery-dtpckr-next-month')) { m ++; }
						v = new Date(y,m,d);
						if(t.hasClass('jquery-dtpckr-item-disabled')) {
							return false;
						}
						switch(this.view) {
							case 'dates':
								if(this.disabled) {
									return false;
								}
								switch(this.settings.mode) {
									case 'date':
										this.selected = v;
										break;
									case 'datetime':
										if(this.selected) {
											h = this.selected.getHours();
											i = this.selected.getMinutes();
											s = this.selected.getSeconds();
										}
										this.selected = v;
										this.selected.setHours(h);
										this.selected.setMinutes(i);
										this.selected.setSeconds(s);
										break;
									case 'range':
										if(this.selected[0] && this.selected[0] === this.selected[1]) {
											this.selected[1] = v;
											if(this.selected[1] < this.selected[0]) {
												tmp1 = this.selected[0];
												this.selected[0] = this.selected[1];
												this.selected[1] = tmp1;
											}
										}
										else {
											this.selected[0] = v;
											this.selected[1] = v;
										}
										break;
									case 'rangetime':
										if(this.selected[0]) {
											h = this.selected[0].getHours();
											i = this.selected[0].getMinutes();
											s = this.selected[0].getSeconds();
										}
										if(this.selected[1]) {
											h1 = this.selected[1].getHours();
											i1 = this.selected[1].getMinutes();
											s1 = this.selected[1].getSeconds();
										}
										if(this.selected[0] && this.selected[1] - this.selected[0] < 24*60*60*1000 - 1) {
											this.selected[1] = v;
											if(this.selected[1] < this.selected[0]) {
												tmp1 = this.selected[0];
												this.selected[0] = this.selected[1];
												this.selected[1] = tmp1;
											}
										}
										else {
											this.selected[0] = v;
											this.selected[1] = new Date(v.getTime());
										}
										this.selected[0].setHours(h);
										this.selected[0].setMinutes(i);
										this.selected[0].setSeconds(s);
										this.selected[1].setHours(h1);
										this.selected[1].setMinutes(i1);
										this.selected[1].setSeconds(s1);
										break;
									case 'multiple':
										if(t.hasClass('jquery-dtpckr-item-selected')) {
											$.each(this.selected, $.proxy(function (i, vv) {
												if(v - vv === 0) {
													this.selected = $.vakata.array_remove(this.selected, i);
													return false;
												}
											}, this));
										}
										else {
											this.selected.push(new Date(y,m,d));
										}
										break;
								}
								this.dates();
								this._trigger_change();
								//this.control.find('.jquery-dtpckr-item-selected').removeClass('jquery-dtpckr-item-selected');
								//t.addClass('jquery-dtpckr-item-selected');
								// selection
								break;
							case 'months':
								this.cur_m = this.control.children('table').find('td').index(t);
								this.dates();
								break;
							case 'years':
								this.cur_y = parseInt(t.text(),10);
								this.months();
								break;
						}
					}, this));
			if(this.settings.trigger && this.settings.style === 'popup') {
				this.trigger = $(this.settings.trigger);
				if(this.trigger.length) {
					this.trigger.click($.proxy(function (e) {
						e.preventDefault();
						this[this.control.is(':visible') ? 'hide' : 'show']();
						$(e.target).blur();
					}, this));
				}
			}
		},
		/**
		 * `$.dtpckr.reference()` get an instance by some selector.
		 *
		 * __Parameters__
		 *
		 * * `needle` - a DOM element / jQuery object to search by.
		 */
		reference : function (needle) {
			return $(needle).closest('.jquery-dtpckr').data('dtpckr');
		}
	};
	/**
	 * ### jQuery $().dtpckr method
	 *
	 * `$(selector).dtpckr()` is used to create an instance on the selector or to invoke a command on a instance.
	 *
	 * __Examples__
	 *
	 *	$('#container').dtpckr();
	 *	$('#container').dtpckr({ option : value });
	 *	$('#container').dtpckr('open_node', '#branch_1');
	 *
	 */
	$.fn.dtpckr = function (arg) {
		// check for string argument
		var is_method = (typeof arg === 'string'),
			args = Array.prototype.slice.call(arguments, 1),
			result = null;
		this.each(function () {
			// get the instance (if there is one) and method (if it exists)
			var instance = $(this).data('dtpckr'),
				method = is_method && instance ? instance[arg] : null;
			// if calling a method, and method is available - execute on the instance
			result = is_method && method ?
				method.apply(instance, args) :
				null;
			// if there is no instance - create one
			if(!instance) {
				$.dtpckr.create(this, arg);
			}
			// if there was a method call which returned a result - break and return the value
			if(result !== null && result !== undefined) {
				return false;
			}
		});
		// if there was a method call with a valid return value - return that, otherwise continue the chain
		return result !== null && result !== undefined ?
			result : this;
	};
	$.expr.pseudos.dtpckr = $.expr.createPseudo(function(search) {
		return function(a) {
			return $(a).hasClass('jquery-dtpckr') &&
				$(a).data('dtpckr') !== undefined;
		};
	});

	$.dtpckr.instance.prototype = {
		_trigger_change : function (skip_callback, skip_update, skip_hide) {
			var v = null,
				val = this.selected, tmp = null;
			switch(this.settings.mode) {
				case 'range':
				case 'rangetime':
					v = [].concat(val);
					$.each(v, $.proxy(function (i, vv) {
						v[i] = $.dtpckr.formatDate(this.format, vv, this.settings.localize);
					}, this));
					v = v[0] && v[1] ? v.join(' - ') : '';
					break;
				case 'multiple':
					v = [].concat(val);
					$.each(v, $.proxy(function (i, vv) {
						v[i] = $.dtpckr.formatDate(this.format, vv, this.settings.localize);
					}, this));
					tmp = [];
					$.each(v, function (i, vv) {
						if(vv !== '') { tmp.push(vv); }
					});
					v = tmp.length ? tmp.join(', ') : '';
					break;
				case 'date':
				case 'datetime':
					v = $.dtpckr.formatDate(this.format, val, this.settings.localize) || '';
					break;
			}
			if(this.element.is('input')) {
				if(!skip_update) {
					// non-visible fields fail in IE8
					if(this.element.is(':focus')) {
						try { tmp = $.vakata.selection.elm_get_caret(this.element[0]); } catch(e) { tmp = null; }
					}
					this.element.val(v).change();
					if(tmp !== null) {
						try { $.vakata.selection.elm_set_caret(this.element[0], tmp); } catch(ignore) { }
					}
				}
			}
			if(this.settings.style === 'popup' && this.settings.closeChange && !skip_hide) {
				this.hide();
			}
			if(!skip_callback) {
				this.settings.change.call(this, this.selected, v);
			}
		},
		/**
		 * `show()` shows the datepicker if in _popup_ mode
		 */
		show : function () {
			if(this.settings.style !== 'popup' || this.control.is(':visible')) { return; }
			this.dates();
			this.control.show();
			$.dtpckr.blend(true);
			this.control.find('.jquery-dtpckr-time').find('input').each(function () { $(this).tmpckr('position_select'); });
		},
		/**
		 * `hide()` hides the datepicker if in _popup_ mode
		 */
		hide : function () {
			if(this.settings.style !== 'popup' || !this.control.is(':visible')) { return; }
			this.control.hide();
			$.dtpckr.blend();
			this.settings.hide.call(this, this.selected);
		},
		/**
		 * `set(data)` sets the value for the datepicker
		 *
		 * __Parameters__
		 *
		 * * `data` a single date, an array of two dates for range pickers, or an array of many dates for multiple. Data can be either a date, a string in the required format, or a relative date
		 */
		set : function (data, skip_callback, skip_update, skip_hide) {
			var tmp;
			switch(this.settings.mode) {
				case "date":
				case "datetime":
					this.selected = null;
					if(data) {
						this.selected = $.dtpckr.determineDate(this.format, data, this.settings.localize, null);
						if(this.selected && this.maxDate && this.selected > this.maxDate) {
							this.selected = this.maxDate;
							skip_update = false;
						}
						if(this.selected && this.minDate && this.selected < this.minDate) {
							this.selected = this.minDate;
							skip_update = false;
						}
						tmp = this.selected || new Date();
					}
					break;
				case "range":
				case "rangetime":
					this.selected = [null, null];
					if(data && typeof data === 'string' && data.indexOf(' - ') !== -1) {
						data = data.split(' - ');
					}
					if(data && $.isArray(data) && data.length === 2) {
						this.selected[0] = $.dtpckr.determineDate(this.format, data[0], this.settings.localize, null);
						this.selected[1] = $.dtpckr.determineDate(this.format, data[1], this.settings.localize, null);
					}
					if(data && typeof data === 'string') {
						this.selected[0] = $.dtpckr.determineDate(this.format, data, this.settings.localize, null);
						this.selected[1] = $.dtpckr.determineDate(this.format, data, this.settings.localize, null);
					}
					if(this.selected[0] && this.maxDate && this.selected[0] > this.maxDate) {
						this.selected[0] = this.maxDate;
						skip_update = false;
					}
					if(this.selected[0] && this.minDate && this.selected[0] < this.minDate) {
						this.selected[0] = this.minDate;
						skip_update = false;
					}
					if(this.selected[1] && this.maxDate && this.selected[1] > this.maxDate) {
						this.selected[1] = this.maxDate;
						skip_update = false;
					}
					if(this.selected[1] && this.minDate && this.selected[1] < this.minDate) {
						this.selected[1] = this.minDate;
						skip_update = false;
					}
					tmp = this.selected[1] || new Date();
					break;
				case "multiple":
					this.selected = [];
					if(data && typeof data === 'string' && data.indexOf(', ') !== -1) {
						data = data.split(', ');
					}
					if(data && $.isArray(data)) {
						$.each(data, $.proxy(function (i, v) {
							tmp = $.dtpckr.determineDate(this.format, v, this.settings.localize, null);
							if(tmp) {
								if(this.maxDate && tmp > this.maxDate) {
									tmp = null;
									skip_update = false;
								}
								if(this.minDate && tmp < this.minDate) {
									tmp = null;
									skip_update = false;
								}
								if(tmp) {
									this.selected.push(tmp);
								}
							}
						}, this));
						tmp = tmp || new Date();
					}
					if(data && typeof data === 'string') {
						this.selected[0] = $.dtpckr.determineDate(this.format, data, this.settings.localize, null);
						tmp = this.selected[0] || new Date();
						if(this.selected[0] && this.maxDate && this.selected[0] > this.maxDate) {
							this.selected[0] = this.maxDate;
							skip_update = false;
						}
						if(this.selected[0] && this.minDate && this.selected[0] < this.minDate) {
							this.selected[0] = this.minDate;
							skip_update = false;
						}
					}
					break;
			}
			tmp = tmp || new Date();
			this.cur_d = tmp.getDate();
			this.cur_m = tmp.getMonth();
			this.cur_y = tmp.getFullYear();
			this.dates();
			this._trigger_change(skip_callback, skip_update, skip_hide);
		},
		/**
		 * `get(formatted)` returns the current selection
		 *
		 * __Parameters__
		 *
		 * * `formatted` if _true is passed, the values are formatted, otherwise date objects are returned
		 */
		get : function (formatted) {
			if(!formatted) { return this.selected; }
			var res = $.isArray(this.selected) ? [].concat(this.selected) : this.selected;
			if($.isArray(res)) {
				$.each(res, $.proxy(function (i, v) {
					res[i] = v ? $.dtpckr.formatDate(this.format, v, this.settings.localize) : null;
				}, this));
				return res;
			}
				return res ? $.dtpckr.formatDate(this.format, res, this.settings.localize) : null;
		},
		set_min_date : function (dt) {
			this.minDate = dt ? $.dtpckr.determineDate(this.format, dt, this.settings.localize, null) : null;
		},
		set_max_date : function (dt) {
			this.maxDate = dt ? $.dtpckr.determineDate(this.format, dt, this.settings.localize, null) : null;
		},
		get_min_date : function (formatted) {
			return formatted ? $.dtpckr.formatDate(this.format, this.minDate, this.settings.localize) : this.minDate;
		},
		get_max_date : function (formatted) {
			return formatted ? $.dtpckr.formatDate(this.format, this.maxDate, this.settings.localize) : this.maxDate;
		},
		next : function () {
			switch(this.view) {
				case 'years':
					this.cur_y += 9;
					this.years();
					break;
				case 'months':
					this.cur_y += 1;
					this.months();
					break;
				case 'dates':
					this.cur_m += 1;
					if(this.cur_m > 11) {
						this.cur_y += 1;
						this.cur_m = 0;
					}
					this.dates();
					break;
			}
		},
		prev : function () {
			switch(this.view) {
				case 'years':
					this.cur_y -= 9;
					this.years();
					break;
				case 'months':
					this.cur_y -= 1;
					this.months();
					break;
				case 'dates':
					this.cur_m -= 1;
					if(this.cur_m < 0) {
						this.cur_y -= 1;
						this.cur_m = 11;
					}
					this.dates();
					break;
			}
		},

		_date_class : function (d) {
			var cls = [],
				day = ['sun','mon','tue','wed','thu','fri','sat'],
				sel = this.selected,
				e = new Date(d.getTime()),
				f = new Date(d.getTime()),
				t = new Date(),
				r;
			e.setHours(23);
			e.setMinutes(59);
			e.setSeconds(59);
			if(sel && !$.isArray(sel)) { sel = [ sel ]; }
			cls.push('jquery-dtpckr-day-' + day[d.getDay()]);
			if(t.getFullYear() === d.getFullYear() && t.getMonth() === d.getMonth() && t.getDate() === d.getDate()) {
				cls.push('jquery-dtpckr-today');
			}
			if(sel) {
				if(this.settings.mode.indexOf('date') !== -1 || this.settings.mode === 'multiple') {
					$.each(sel, function (i, v) {
						if(v && v.getFullYear() === d.getFullYear() && v.getMonth() === d.getMonth() && v.getDate() === d.getDate()) {
							cls.push('jquery-dtpckr-item-selected');
						}
					});
				}
				if(this.settings.mode.indexOf('range') !== -1) {
					if(sel[0] && sel[1]) {
						if(e >= sel[0] && f <= sel[1]) {
							cls.push('jquery-dtpckr-item-selected');
						}
					}
				}
			}
			if(this.minDate && d < this.minDate) {
				cls.push('jquery-dtpckr-item-disabled');
			}
			if(this.maxDate && d > this.maxDate) {
				cls.push('jquery-dtpckr-item-disabled');
			}
			r = this.settings.render(d);
			if(r && $.isArray(r) && r.length) {
				cls = cls.concat(r);
			}
			return cls.join(' ');
		},
		_draw_month : function (y, m) {
			var str = '',
				tmp, i, j, k, l, dat;
			str += '<thead><tr>';
			if(this.settings.showWeek) {
				str += '<th class="jquery-dtpckr-week">&#160;</th>';
			}
			tmp = this.settings.localize.dayNamesShort.concat(this.settings.localize.dayNamesShort);
			for(i = this.settings.localize.firstDay, j = this.settings.localize.firstDay + 7; i < j; i++) {
				str += '<th class="jquery-dtpckr-day">'+tmp[i]+'</th>';
			}
			str += '</thead></tr>';
			str += '<tbody>';
			i = this.settings.localize.firstDay;
			j = $.dtpckr.getFirstDayOfMonth(y, m);
			k = 0;
			l = (j+7) - (i+7) - 1;
			if(l < 0) { l += 7; }
			tmp = $.dtpckr.getDaysInMonth(y, m - 1);
			if(i !== j) {
				str += '<tr>';
				if(this.settings.showWeek) {
					str += '<th class="jquery-dtpckr-week">'+$.dtpckr.iso8601Week(new Date(y, m, 1))+'</th>';
				}
			}
			while(true) {
				i = i % 7;
				if(i === j) { break; }
				dat = new Date(y,m-1,(tmp - l));
				str += '<td class="jquery-dtpckr-item jquery-dtpckr-day jquery-dtpckr-prev-month '+this._date_class(dat)+'">'+(tmp - l)+'</td>';
				i++;
				k++;
				l--;
			}
			tmp = $.dtpckr.getDaysInMonth(y, m);
			for(l = 1; l <= tmp; l++) {
				if(k % 7 === 0) {
					str += '<tr>';
					if(this.settings.showWeek) {
						str += '<th class="jquery-dtpckr-week">'+$.dtpckr.iso8601Week(new Date(y, m, l))+'</th>';
					}
				}
				i = i % 7;
				dat = new Date(y,m,l);
				str += '<td class="jquery-dtpckr-item jquery-dtpckr-day '+this._date_class(dat)+'">'+l+'</td>';
				k++;
				i++;
				if(k % 7 === 0) {
					str += '</tr>';
				}
			}
			if(k % 7 !== 0) {
				l = 1;
				while(true) {
					dat = new Date(y,m+1,l);
					str += '<td class="jquery-dtpckr-item jquery-dtpckr-day jquery-dtpckr-next-month '+this._date_class(dat)+'">'+l+'</td>';
					l++;
					i++;
					k++;
					if(k % 7 === 0) { break; }
				}
				str += '</tr>';
			}
			str += '</tbody>';
			return str;
		},

		dates : function () {
			this.view = 'dates';
			if(this.settings.style === 'popup') {
				this.control.width(this.settings.width * this.settings.months);
			}
			var t = this.control.children('.jquery-dtpckr-table-main').clone().removeClass('jquery-dtpckr-table-main').empty(),
				i = this.cur_m - Math.ceil(this.settings.months / 2) + 1,
				j = this.cur_m + Math.floor(this.settings.months / 2),
				m = i,
				y = this.cur_y,
				f = false,
				tmp, v1, v2,
				mnt = [],
				all = [],
				tctrl, w;
			if(m < 0) { m = 11; y--; }
			if(m > 11) { m = 0; y++; }
			this.control.css('height', this.control.height() + 'px').children('table').remove();
			for(i = m; i <= j; i++) {
				tmp = t.clone();
				tmp.append(this._draw_month(this.cur_y, i));
				//tmp.width((100.00/this.settings.months)+'%'); //.find('td:last-child').css('borderRight','10px solid white');
				w = (this.control.width() - (this.settings.months - 1)* 10) / this.settings.months;
				tmp.width( w > 0 ? w : '100%' ).css('marginRight', i===j?'0':'10px');
				tmp.data('y', this.cur_y);
				tmp.data('m', i);
				if(i === this.cur_m) {
					tmp.addClass('jquery-dtpckr-table-main');
				}
				all.push(this.settings.localize.monthNames[m] + ' ' + y);
				if(!f) {
					f = true;
					mnt.push(this.settings.localize.monthNames[m] + ' ' + y);
				}
				if(i === j) {
					mnt.push(this.settings.localize.monthNames[m] + ' ' + y);
				}
				tmp.appendTo(this.control);
				m++;
				if(m < 0) { m = 11; y--; }
				if(m > 11) { m = 0; y++; }
			}
			this.control.css('height', 'auto');
			if(this.settings.months === 1) {
				this.control.find('.jquery-dtpckr-current').text(this.settings.localize.monthNames[this.cur_m] + ' ' + this.cur_y);
			}
			else {
				this.control.find('.jquery-dtpckr-current').text(mnt.join(' - '));
				m = Math.floor((this.control.width() - (this.settings.months - 1)*10) / this.settings.months - 50);
				this.control.find('.jquery-dtpckr-current')
					.html('<span>' + all.join('</span><span>') + '</span>')
					.children('span').width(m).css('marginLeft','10px')
					.eq(0).css({'marginLeft':'0','paddingLeft':'0'}).end()
					.eq(-1).css({'paddingRight':'0'}).end()
					.eq(this.control.find('.jquery-dtpckr-table').index(this.control.find('.jquery-dtpckr-table-main'))).addClass('jquery-dtpckr-current-nav');
			}
			if(this.settings.mode.indexOf('time') !== -1) {
				this.control.find('.jquery-dtpckr-time').show();
			}
			this.control.find('tbody > tr:nth-child(2n)').addClass('jquery-dtpckr-even-row').end().find('tbody > tr:nth-child(2n+1)').addClass('jquery-dtpckr-odd-row');
			this.control.find('.jquery-dtpckr-time').appendTo(this.control).find('input').each(function () { $(this).tmpckr('position_select'); }); // position_select was commented out?
			this.control.find('.jquery-dtpckr-clear').appendTo(this.control);

			if(this.settings.mode.indexOf('time') !== -1 && this.settings.time) {
				tctrl = false;
				if(this.settings.mode === 'rangetime') {
					v1 = '';
					v2 = '';
					if(this.settings.time.hours) {
						if(this.selected[0]) { v1 += ('00' + this.selected[0].getHours()).slice(-2); }
						if(this.selected[1]) { v2 += ('00' + this.selected[1].getHours()).slice(-2); }
					}
					if(this.settings.time.minutes) {
						if(this.selected[0]) { v1 += ':' + ('00' + this.selected[0].getMinutes()).slice(-2); }
						if(this.selected[1]) { v2 += ':' + ('00' + this.selected[1].getMinutes()).slice(-2); }
					}
					if(this.settings.time.seconds) {
						if(this.selected[0]) { v1 += ':' + ('00' + this.selected[0].getSeconds()).slice(-2); }
						if(this.selected[1]) { v2 += ':' + ('00' + this.selected[1].getSeconds()).slice(-2); }
					}
					if(v1) {
						tctrl = this.control.find('.jquery-dtpckr-time').find('[name^="time"]').eq(0);
						tctrl.val(v1);
						tctrl.val($.tmpckr.format(this.settings.time, $.tmpckr.parse(this.settings.time, tctrl.val())));
					}
					if(v2) {
						tctrl = this.control.find('.jquery-dtpckr-time').find('[name^="time"]').eq(1);
						tctrl.val(v2);
						tctrl.val($.tmpckr.format(this.settings.time, $.tmpckr.parse(this.settings.time, tctrl.val())));
					}
				}
				if(this.settings.mode === 'datetime') {
					v1 = '';
					if(this.settings.time.hours) {
						if(this.selected) { v1 += ('00' + this.selected.getHours()).slice(-2); }
					}
					if(this.settings.time.minutes) {
						if(this.selected) { v1 += ':' + ('00' + this.selected.getMinutes()).slice(-2); }
					}
					if(this.settings.time.seconds) {
						if(this.selected) { v1 += ':' + ('00' + this.selected.getSeconds()).slice(-2); }
					}
					if(v1) {
						tctrl = this.control.find('.jquery-dtpckr-time').find('[name^="time"]').eq(0);
						tctrl.val(v1);
						tctrl.val($.tmpckr.format(this.settings.time, $.tmpckr.parse(this.settings.time, tctrl.val())));
					}
				}
			}
			if(this.settings.style === 'popup') {
				this.reposition();
			}
		},
		months : function () {
			this.view = 'months';
			if(this.settings.style === 'popup') {
				this.control.width(this.settings.width);
			}
			this.control.children('table').width('100%').css('marginRight','0');
			this.control.children('table').not('.jquery-dtpckr-table-main').remove();
			this.control.css('height', this.control.height() + 'px').children('table').empty().append(
				'<tbody>'+
					'<tr class="jquery-dtpckr-odd-row">'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[0]+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[1]+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[2]+'</td>'+
					'</tr>'+
					'<tr class="jquery-dtpckr-even-row">'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[3]+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[4]+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[5]+'</td>'+
					'</tr>'+
					'<tr class="jquery-dtpckr-odd-row">'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[6]+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[7]+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[8]+'</td>'+
					'</tr>'+
					'<tr class="jquery-dtpckr-even-row">'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[9]+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[10]+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-month">'+this.settings.localize.monthNamesShort[11]+'</td>'+
					'</tr>'+
				'</tbody>').end().find('.jquery-dtpckr-current').text(this.cur_y).end().find('.jquery-dtpckr-time').hide();
			this.control.css('height', 'auto');
			if(this.settings.style === 'popup') {
				this.reposition();
			}
		},
		years : function () {
			this.view = 'years';
			if(this.settings.style === 'popup') {
				this.control.width(this.settings.width);
			}
			this.control.children('table').width('100%').css('marginRight','0');
			this.control.css('height', this.control.height() + 'px').children('table').empty().append(
				'<tbody>'+
					'<tr class="jquery-dtpckr-odd-row">'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-year">'+(this.cur_y - 4)+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-year">'+(this.cur_y - 3)+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-year">'+(this.cur_y - 2)+'</td>'+
					'</tr>'+
					'<tr class="jquery-dtpckr-even-row">'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-year">'+(this.cur_y - 1)+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-year">'+this.cur_y+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-year">'+(this.cur_y + 1)+'</td>'+
					'</tr>'+
					'<tr class="jquery-dtpckr-odd-row">'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-year">'+(this.cur_y + 2)+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-year">'+(this.cur_y + 3)+'</td>'+
						'<td class="jquery-dtpckr-item jquery-dtpckr-year">'+(this.cur_y + 4)+'</td>'+
					'</tr>'+
				'</tbody>').end().find('.jquery-dtpckr-current').text((this.cur_y - 4) + ' - ' + (this.cur_y + 4)).end().find('.jquery-dtpckr-time').hide();
			this.control.css('height', 'auto');
			if(this.settings.style === 'popup') {
				this.reposition();
			}
		},
		is_disabled : function () {
			return this.disabled;
		},
		/**
		 * `disable()` disables clicking on dates
		 */
		disable : function () {
			this.disabled = true;
			this.control.children('table').addClass('jquery-dtpckr-disabled');
		},
		/**
		 * `enable()` enables clicking on dates
		 */
		enable : function () {
			this.disabled = false;
			this.control.children('table').removeClass('jquery-dtpckr-disabled');
		},
		reposition : function () {
			if(this.settings.style !== 'popup') { return; } // || this.control.is(':visible')
			var isv = this.control.is(':visible') && this.control.css('visibility') !== 'hidden',
				o, eh, w, h, dw, dh, x, y, el;
			this.control.css({'display':'block','visibility':'hidden','left':0,'top':0});
			el = this.trigger && this.trigger.length && !this.element.is(':visible') ? this.trigger : this.element;
			o = el.offset();
			eh = parseInt(el.outerHeight(),10);
			w = parseInt(this.control.outerWidth(),10);
			h = parseInt(this.control.outerHeight(),10);
			dw = $(window).scrollLeft() + $(window).width(); // $(document).width();
			dh = $(window).scrollTop() + $(window).height(); // $(document).height();
			x = parseInt(o.left,10);
			y = parseInt(o.top,10) + eh;
			if(x + w + 10 > dw) {
				x = dw - (w + 10);
			}
			if(y + h + 10 > dh) {
				y = y - (eh + h);
			}
			if(!isv) {
				this.control.css({'display':'none','visibility':'visible','left': x + 'px','top': y  + 'px'});
			}
			else {
				this.control.css({'visibility':'visible','left': x + 'px','top': y  + 'px'});
			}
			if(isv) { this.show(); }
		}
	};

	$.dtpckr.blend = function (show) {
		var b = show || $('.jquery-dtpckr-popup:visible').length,
			m = $('meta[name="viewport"]'),
			v = m.length ? m.attr("content") : false;
		if(b && m.length) {
			m.attr("content", 'width=device-width, minimum-scale=1.0, maximum-scale=1.0, initial-scale=1.0, user-scalable=no');
			$('body').height();
			m.attr("content", v);
		}
		$('body .jquery-dtpckr-blend')
			.width($(document).width())
			.height($(document).height())
			[ b ? 'addClass' : 'removeClass' ]('jquery-dtpckr-blend-on');
	};
	// HELPERS
	$.dtpckr.getDaysInMonth = function(year, month) {
		return 32 - (new Date(year, month, 32)).getDate();
	};
	$.dtpckr.getFirstDayOfMonth = function(year, month) {
		return new Date(year, month, 1).getDay();
	};
	$.dtpckr.iso8601Week = function(date) {
		var time,
			checkDate = new Date(date.getTime());
		// Find Thursday of this week starting on Monday
		checkDate.setDate(checkDate.getDate() + 4 - (checkDate.getDay() || 7));
		time = checkDate.getTime();
		checkDate.setMonth(0); // Compare with Jan 1
		checkDate.setDate(1);
		return Math.floor(Math.round((time - checkDate) / 86400000) / 7) + 1;
	};
	$.dtpckr.parseDate = function (format, value, settings) {
		if (!format || !value) { throw "Invalid arguments"; }
		value = value.toString();
		if (value === "") { return null; }
		if (!settings) { settings = {}; }

		var cond = true,
			ampm = value.match(/ pm$/i) ? 12 : 0,
			iFormat, dim, extra,
			iValue			= 0,
			dayNamesShort	= settings.dayNamesShort || $.dtpckr.defaults.localize.dayNamesShort,
			dayNames		= settings.dayNames || $.dtpckr.defaults.localize.dayNames,
			monthNamesShort	= settings.monthNamesShort || $.dtpckr.defaults.localize.monthNamesShort,
			monthNames		= settings.monthNames || $.dtpckr.defaults.localize.monthNames,
			year			= -1,
			month			= -1,
			day				= -1,
			doy				= -1,
			hour			= 0,
			minute			= 0,
			second			= 0,
			literal			= false,
			date,
			// Check whether a format character is doubled
			lookAhead = function(match) {
				var matches = (iFormat + 1 < format.length && format.charAt(iFormat + 1) === match);
				if (matches) {
					iFormat++;
				}
				return matches;
			},
			// Extract a number from the string value
			getNumber = function(match) {
				var isDoubled = lookAhead(match),
					size = (match === "@" ? 14 : (match === "!" ? 20 :
					(match === "y" && isDoubled ? 4 : (match === "o" ? 3 : 2)))),
					digits = new RegExp("^\\d{1," + size + "}"),
					num = value.substring(iValue).match(digits);
				if (!num) {
					throw "Missing number at position " + iValue;
				}
				iValue += num[0].length;
				return parseInt(num[0], 10);
			},
			// Extract a name from the string value and convert to an index
			getName = function(match, shortNames, longNames) {
				var index = -1,
					names = $.map(lookAhead(match) ? longNames : shortNames, function (v, k) {
						return [ [k, v] ];
					}).sort(function (a, b) {
						return -(a[1].length - b[1].length);
					});

				$.each(names, function (i, pair) {
					var name = pair[1];
					if (value.substr(iValue, name.length).toLowerCase() === name.toLowerCase()) {
						index = pair[0];
						iValue += name.length;
						return false;
					}
				});
				if (index !== -1) {
					return index + 1;
				}
				throw "Unknown name at position " + iValue;
			},
			// Confirm that a literal character matches the string value
			checkLiteral = function() {
				if (value.charAt(iValue) !== format.charAt(iFormat)) {
					throw "Unexpected literal at position " + iValue;
				}
				iValue++;
			};
		value = value.replace(/ (am|pm)$/i,'');
		for (iFormat = 0; iFormat < format.length; iFormat++) {
			if (literal) {
				if (format.charAt(iFormat) === "'" && !lookAhead("'")) {
					literal = false;
				} else {
					checkLiteral();
				}
			} else {
				switch (format.charAt(iFormat)) {
					case "H":
						hour = getNumber("H");
						break;
					case "i":
						minute = getNumber("i");
						break;
					case "s":
						second = getNumber("s");
						break;

					case "d":
						day = getNumber("d");
						break;
					case "D":
						getName("D", dayNamesShort, dayNames);
						break;
					case "o":
						doy = getNumber("o");
						break;
					case "m":
						month = getNumber("m");
						break;
					case "M":
						month = getName("M", monthNamesShort, monthNames);
						break;
					case "y":
						year = getNumber("y");
						break;
					case "@":
						date = new Date(getNumber("@"));
						year = date.getFullYear();
						month = date.getMonth() + 1;
						day = date.getDate();
						break;
					case "!":
						date = new Date((getNumber("!") - (((1970 - 1) * 365 + Math.floor(1970 / 4) - Math.floor(1970 / 100) + Math.floor(1970 / 400)) * 24 * 60 * 60 * 10000000)) / 10000);
						year = date.getFullYear();
						month = date.getMonth() + 1;
						day = date.getDate();
						break;
					case "'":
						if (lookAhead("'")){
							checkLiteral();
						} else {
							literal = true;
						}
						break;
					default:
						checkLiteral();
				}
			}
			if(iValue >= value.length) {
				break;
			}
		}

		if (iValue < value.length){
			extra = value.substr(iValue);
			if (!/^\s+/.test(extra)) {
				throw "Extra/unparsed characters found in date: " + extra;
			}
		}


		if (year === -1) {
			year = new Date().getFullYear();
		} else if (year < 100) {
			year += new Date().getFullYear() - new Date().getFullYear() % 100;
		}

		if (doy > -1) {
			month = 1;
			day = doy;
			do {
				dim = $.dtpckr.getDaysInMonth(year, month - 1);
				if (day <= dim) {
					break;
				}
				month++;
				day -= dim;
			} while (cond);
		}

		hour += hour < 12 ? ampm : 0;
		date = new Date(year, month - 1, day, hour, minute, second);

		if (date.getFullYear() !== year || date.getMonth() + 1 !== month || date.getDate() !== day) {
			throw "Invalid date"; // E.g. 31/02/00
		}
		return date;
	};
	$.dtpckr.formatDate = function (format, date, settings) {
		if (!date) {
			return "";
		}
		if (!settings) { settings = {}; }

		var iFormat,
			dayNamesShort	= settings.dayNamesShort || $.dtpckr.defaults.localize.dayNamesShort,
			dayNames		= settings.dayNames || $.dtpckr.defaults.localize.dayNames,
			monthNamesShort	= settings.monthNamesShort || $.dtpckr.defaults.localize.monthNamesShort,
			monthNames		= settings.monthNames || $.dtpckr.defaults.localize.monthNames,
			ampm			= settings.ampm || false,
			// Check whether a format character is doubled
			lookAhead = function(match) {
				var matches = (iFormat + 1 < format.length && format.charAt(iFormat + 1) === match);
				if (matches) {
					iFormat++;
				}
				return matches;
			},
			// Format a number, with leading zero if necessary
			formatNumber = function(match, value, len) {
				var num = value.toString();
				if (lookAhead(match)) {
					while (num.length < len) {
						num = "0" + num;
					}
				}
				return num;
			},
			// Format a name, short or long as requested
			formatName = function(match, value, shortNames, longNames) {
				return (lookAhead(match) ? longNames[value] : shortNames[value]);
			},
			output = "",
			literal = false;

		ampm = ampm && format.match('HH');

		if (date) {
			for (iFormat = 0; iFormat < format.length; iFormat++) {
				if (literal) {
					if (format.charAt(iFormat) === "'" && !lookAhead("'")) {
						literal = false;
					} else {
						output += format.charAt(iFormat);
					}
				} else {
					switch (format.charAt(iFormat)) {
						case "H":
							output += formatNumber("H", (ampm && date.getHours() > 11 ? date.getHours() - 12 : date.getHours()), 2);
							break;
						case "i":
							output += formatNumber("i", date.getMinutes(), 2);
							break;
						case "s":
							output += formatNumber("s", date.getSeconds(), 2);
							break;

						case "d":
							output += formatNumber("d", date.getDate(), 2);
							break;
						case "D":
							output += formatName("D", date.getDay(), dayNamesShort, dayNames);
							break;
						case "o":
							output += formatNumber("o",
								Math.round((new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime() - new Date(date.getFullYear(), 0, 0).getTime()) / 86400000), 3);
							break;
						case "m":
							output += formatNumber("m", date.getMonth() + 1, 2);
							break;
						case "M":
							output += formatName("M", date.getMonth(), monthNamesShort, monthNames);
							break;
						case "y":
							output += (lookAhead("y") ? date.getFullYear() :
								(date.getYear() % 100 < 10 ? "0" : "") + date.getYear() % 100);
							break;
						case "@":
							output += date.getTime();
							break;
						case "!":
							output += date.getTime() * 10000 + (((1970 - 1) * 365 + Math.floor(1970 / 4) - Math.floor(1970 / 100) + Math.floor(1970 / 400)) * 24 * 60 * 60 * 10000000);
							break;
						case "'":
							if (lookAhead("'")) {
								output += "'";
							} else {
								literal = true;
							}
							break;
						default:
							output += format.charAt(iFormat);
					}
				}
			}
		}
		if(ampm) {
			output += ampm && date.getHours() > 11 ? ' pm' : ' am';
		}
		return output;
	};
	$.dtpckr.determineDate = function(format, currentDate, settings, defaultDate) {
		var offsetNumeric = function(offset) {
				var date = new Date();
				date.setDate(date.getDate() + offset);
				return date;
			},
			offsetString = function(offset) {
				try {
					return $.dtpckr.parseDate(format, offset, settings);
				}
				catch (ignore) { }

				var	tmp =  offset.toLowerCase().match(/^c/) ? currentDate : null,
					date = tmp || new Date(),
					year = date.getFullYear(),
					month = date.getMonth(),
					day = date.getDate(),
					hour = date.getHours(),
					minute = date.getMinutes(),
					second = date.getSeconds(),
					pattern = /([+\-]?[0-9]+)\s*(d|D|w|W|m|M|y|Y|h|H|i|I|s|S)?/g,
					matches = pattern.exec(offset);
				while (matches) {
					switch (matches[2] || "d") {
						case "d" : case "D" :
							day += parseInt(matches[1],10); break;
						case "w" : case "W" :
							day += parseInt(matches[1],10) * 7; break;
						case "m" : case "M" :
							month += parseInt(matches[1],10);
							day = Math.min(day, $.dtpckr.getDaysInMonth(year, month));
							break;
						case "y": case "Y" :
							year += parseInt(matches[1],10);
							day = Math.min(day, $.dtpckr.getDaysInMonth(year, month));
							break;
						case "h": case "H" :
							hour += parseInt(matches[1],10);
							break;
						case "i": case "I" :
							minute += parseInt(matches[1],10);
							break;
						case "s": case "S" :
							second += parseInt(matches[1],10);
							break;
					}
					matches = pattern.exec(offset);
				}
				return new Date(year, month, day, hour, minute, second);
			},
			newDate = (!currentDate || currentDate === "" ? defaultDate : (typeof currentDate === "string" ? offsetString(currentDate) :
				(typeof currentDate === "number" ? (isNaN(currentDate) ? defaultDate : offsetNumeric(currentDate)) : new Date(currentDate.getTime()))));

		newDate = (newDate && newDate.toString() === "Invalid Date" ? defaultDate : newDate);
		/*!
		if (newDate) {
			newDate.setHours(0);
			newDate.setMinutes(0);
			newDate.setSeconds(0);
			newDate.setMilliseconds(0);
		}
		*/
		return newDate;
	};

	$(function () {
		$('body')
			.append('<div class="jquery-dtpckr-blend"></div>')
			.find('.jquery-dtpckr-blend').on('click', function () { $(document).trigger('mousedown'); });
		$(document).bind('mousedown.dtpckr', function (e) {
			var t = $(e.target).closest('.jquery-dtpckr'),
				i = $(e.target).data('dtpckr');
			if(i) { t = i.control; }
			//$('.jquery-dtpckr-popup').not(t).hide(); /*.each(function () { $(this).find('.jquery-dtpckr-time-single').tmpckr('hide_select'); });*/
			$('.jquery-dtpckr-popup').not(t).each(function () { var d = $(this).data('dtpckr'); if(d && d.hide && !d.settings.persist) { d.hide(); } });
			$.dtpckr.blend();
		});
	});

	if(!$.vakata) {
		$.vakata = {};
	}
	$.vakata.array_remove = function(array, from, to) {
		var rest = array.slice((to || from) + 1 || array.length);
		array.length = from < 0 ? array.length + from : from;
		array.push.apply(array, rest);
		return array;
	};
}(jQuery));


(function ($, undefined) {
	"use strict";

	// prevent another load? maybe there is a better way?
	if($.tmpckr) { return; }

	var instance_counter = 0;

	$.tmpckr = {
		version : '0.0.2',
		defaults : {
			hours		: true,
			minutes		: true,
			seconds		: false,
			ampm		: false,
			restrict	: false,
			combo		: false,
			calculate	: false
		},
		instance : function (el, options) {
			this.id = ++instance_counter;
			this.element = $(el);
			this.settings = options;
			this.value = false;
			this.init();
		}
	};

	$.tmpckr.instance.prototype = {
		init : function () {
			this.element.addClass('jquery-tmpckr');
			this.element.bind("destroyed", $.proxy(this.teardown, this));
			this.valid = [];
			this.select = $();
			this.swidth = false;

			var tmp = { 'hours':[], 'minutes':[], 'seconds':[] };
			$.each(['hours','minutes','seconds'], $.proxy(function (i,v) {
				var j = 0, m = v === 'hours' ? 24 : 60;
				if(this.settings[v]) {
					if($.isArray(this.settings[v])) {
						tmp[v] = this.settings[v].concat([]);
					}
					else {
						for(j = 0; j < m; j += (parseInt(this.settings[v],10) || 1)) {
							tmp[v].push(j);
						}
					}
				}
				else {
					tmp[v].push(0);
				}
			}, this));
			$.each(tmp.hours, $.proxy(function (ih, hh) {
				$.each(tmp.minutes, $.proxy(function (im, mm) {
					$.each(tmp.seconds, $.proxy(function (is, ss) {
						var vv = '',
							ap = '',
							hhh = hh;
						if(this.settings.ampm) {
							if(hhh > 12) {
								hhh -= 12;
								ap += ' pm';
							}
							else if(hhh === 12) {
								ap += ' pm';
							}
							else {
								ap += ' am';
							}
						}
						if(this.settings.hours)   { vv += ('00' + hhh).slice(-2); }
						if(this.settings.minutes) { vv += (this.settings.hours ? ':' : '') + ('00' + mm).slice(-2); }
						if(this.settings.seconds) { vv += (this.settings.hours || this.settings.minutes ? ':' : '') + ('00' + ss).slice(-2); }
						vv += ap;
						this.valid.push([vv,$.tmpckr.parse(this.settings,vv)]);
					}, this));
				}, this));
			}, this));
			if(this.settings.combo) {
				this.select = $('<select class="jquery-tmpckr-select">');
				// this.select.append('<option disabled="disabled"></option>');
				$.each(this.valid, $.proxy(function (i,v) {
					this.select.append('<option value="'+v[0]+'">'+v[0]+'</option>');
				}, this));
				if(!this.swidth) {
					this.swidth = $.vakata.get_scrollbar_width() || 18;
				}
				this.select.css('margin','0').outerWidth(this.element.outerWidth() + this.swidth).outerHeight(this.element.outerHeight());
				this.element.css({
					'zIndex'	: 999,
					'position'	: this.element.css('position') === 'absolute' ? 'absolute' : 'relative'
				});
				this.select.hide().css({
					'position'	: 'absolute',
					'zIndex'	: 998
				});//.appendTo(this.element.offsetParent());
				if(this.element.attr('id')) {
					this.select.attr('id', this.element.attr('id') + '_tmpckrselect');
				}
				this.position_select();

				/*!
				.appendTo('body');
				if(!this.element.is(':visible')) {
					this.select.hide();
				}
				this.position_select();
				setTimeout($.proxy(function () {
					this.position_select();
				}, this), 150);
				*/
			}
			this.bind();
			this.change();
			this.trigger("ready");
		},
		destroy : function () {
			this.element.unbind("destroyed", this.teardown);
			this.teardown();
		},
		teardown : function () {
			this.unbind();
			this.element
				.removeClass('jquery-tmpckr')
				.removeData('tmpckr');
			this.select.remove();
			this.select = null;
			this.element = null;
		},
		bind : function () {
			if(this.settings.combo) {
				this.select.on('change', $.proxy(function (e) {
					this.element.val($(e.target).val()).change().focus();
					this.change();
				}, this));
			}
			var m		= false,
				c		= false,
				to		= false,
				modes	= [];
			if(this.settings.hours)   { modes.push('Hours'); }
			if(this.settings.minutes) { modes.push('Minutes'); }
			if(this.settings.seconds) { modes.push('Seconds'); }
			if(this.settings.ampm)    { modes.push('AMPM'); }
			this.element
				.on('keydown.tmpckr', $.proxy(function (e) {
					// allow tab navigation
					if(e.which === 9) {
						return;
					}
					// prevent non numeric
					if(!(e.which >= 48 && e.which <= 57) && !(e.which >= 96 && e.which <= 105)) {
						e.preventDefault();
					}
					var current = $.tmpckr.parse(this.settings, this.element.val());
					if(e.which === 38) { // up key
						if(m === 'AMPM') {
							current.setHours(current.getHours() + 12);
						}
						else {
							current['set' + m](current['get' + m]() + 1);
						}
						this.element.val($.tmpckr.format(this.settings, current));
						this.change(1);
						$.vakata.selection.elm_set(this.element[0], c, c + 2);
						return;
					}
					if(e.which === 40) { // down key
						if(m === 'AMPM') {
							current.setHours(current.getHours() + 12);
						}
						else {
							current['set' + m](current['get' + m]() - 1);
						}
						this.element.val($.tmpckr.format(this.settings, current));
						this.change(-1);
						$.vakata.selection.elm_set(this.element[0], c, c + 2);
						return;
					}
					// left
					if(e.which === 37) {
						if(c > 0) {
							c -= 3;
							$.vakata.selection.elm_set(this.element[0], c, c + 2);
							m = modes[$.inArray(m, modes) - 1];
						}
						return;
					}
					// right, colon or space
					if(e.which === 39 || e.which === 32 || (e.shiftKey && e.which === 59)) {
						c += 3;
						m = modes[$.inArray(m, modes) + 1];
						if(this.element.val().length < c) {
							c = 0;
							m = modes[0];
						}
						$.vakata.selection.elm_set(this.element[0], c, c + 2);
						return;
					}
					// a
					if(e.which === 65) {
						if(m === 'AMPM' && current.getHours() > 12) {
							current.setHours(current.getHours() - 12);
							this.element.val($.tmpckr.format(this.settings, current));
							this.change();
							$.vakata.selection.elm_set(this.element[0], c, c + 2);
						}
						return;
					}
					// p
					if(e.which === 80) {
						if(m === 'AMPM' && current.getHours() < 12) {
							current.setHours(current.getHours() + 12);
							this.element.val($.tmpckr.format(this.settings, current));
							this.change();
							$.vakata.selection.elm_set(this.element[0], c, c + 2);
						}
						return;
					}
				}, this))
				.on('keyup.tmpckr', $.proxy(function () {
					if(to) { clearTimeout(to); }
					to = setTimeout($.proxy(function () {
						this.set(this.get());
						$.vakata.selection.elm_set(this.element[0], c, c + 2);
					}, this), 1000);
				}, this))
				.on('focus.tmpckr click.tmpckr', $.proxy(function () {
					var r = parseInt($.vakata.selection.elm_get_caret(this.element),10) || 0;
					if(r >= 0 && r < 3) {
						$.vakata.selection.elm_set(this.element[0], 0, 2);
						m = modes[0];
						c = 0;
					}
					if(r >= 3 && r < 6) {
						$.vakata.selection.elm_set(this.element[0], 3, 5);
						m = modes[1];
						c = 3;
					}
					if(r >= 6 && r < 9) {
						$.vakata.selection.elm_set(this.element[0], 6, 8);
						m = modes[2];
						c = 6;
					}
					if(r >= 9) {
						$.vakata.selection.elm_set(this.element[0], 9, 11);
						m = modes[3];
						c = 9;
					}
				}, this))
				.on('blur.tmpckr', $.proxy(function () {
					this.element.change();
				}, this));
		},
		unbind : function () {
			this.element.off('.tmpckr');
			$(document).off('.tmpckr-' + this._id);
		},
		trigger : function (ev, data) {
			if(!data) { data = {}; }
			data.instance = this;
			this.element.triggerHandler(ev.replace('.tmpckr','') + '.tmpckr', data);
		},
		parse : function (value) {
			return $.tmpckr.parse(this.settings, value, this.value);
		},
		format : function (date) {
			return $.tmpckr.format(this.settings, date);
		},
		get : function (formatted) {
			var current = this.parse(this.element.val());
			return formatted ? this.format(current) : current;
		},
		set : function (value, skip_update) {
			if(typeof value === 'string') {
				value = this.parse(value);
			}
			if(!skip_update) {
				this.element.val(this.format(value)); //.blur();
			}
			this.change();
		},
		change : function (dr) {
			var c1 = this.get(true),
				c2 = this.get(),
				r1 = false,
				r2 = false,
				df = false;
			if(!dr) { dr = 0; }
			if(this.settings.restrict) {
				$.each(this.valid, $.proxy(function (i,v) {
					if(df === false || c1 === v[0] || Math.abs(c2 - v[1]) < df) {
						if(dr === 0 || c1 === v[0] || (dr === 1 && v[1] >= c2) || (dr === -1 && v[1] <= c2)) {
							r1 = v[0];
							r2 = v[1];
							if(c1 === v[0]) { return false; }
							df = Math.abs(c2 - v[1]);
						}
					}
				}, this));
				c1 = r1 || this.valid[0][0];
				c2 = r2 || this.valid[0][1];
				this.element.val(c1);
			}
			this.value = c2;
			this.trigger("changed", { 'formatted' : c1, 'raw' : c2 });
		},
		position_select : function () {
			if(this.element.is(':visible')) {
				var o = this.element.position();
				this.select.appendTo(this.element.offsetParent()).show().css({
					'left' : (o.left + jQuery.css(this.element[0], "marginLeft", true)) + 'px',
					'top'  : (o.top  + jQuery.css(this.element[0], "marginTop",  true)) + 'px'
				});
			}
			else {
				this.select.hide();
				//setTimeout($.proxy(function () { this.position_select(); }, this), 250);
			}
		},
		hide_select : function () {
			if(this.settings.combo) {
				this.select.hide();
			}
		},
		show_select : function () {
			if(this.settings.combo) {
				this.position_select();
				this.select.show();
			}
		}
	};

	$.expr.pseudos.tmpckr = $.expr.createPseudo(function(search) {
		return function(a) {
			return $(a).hasClass('jquery-tmpckr') &&
				$(a).data('tmpckr') !== undefined;
		};
	});
	$.fn.tmpckr = function (options) {
		var ret = this,
			arg = arguments;
		this.each(function () {
			var instance = $(this).data('tmpckr');
			if(instance) {
				if(typeof options === 'string' && $.isFunction(instance[options])) {
					ret = instance[options].apply(instance, Array.prototype.slice.call(arg, 1));
					return false;
				}
				return true;
			}
			options = $.extend(true, {}, $.tmpckr.defaults, options);
			instance = new $.tmpckr.instance(this, options);
			$(this).data('tmpckr', instance);
		});
		return ret;
	};

	// helpers
	$.tmpckr.parse = function (options, value, last_value) {
		var h = '', m = '', s = '', d = new Date(), i = 0;
		if(options.hours) {
			while(i < value.length && value.charAt(i).match(/\d{1}/)) {
				h += value.charAt(i);
				i++;
			}
			while(i < value.length && !value.charAt(i).match(/\d/)) { i++; }
		}
		if(options.minutes) {
			while(i < value.length && value.charAt(i).match(/\d{1}/)) {
				m += value.charAt(i);
				i++;
			}
			while(i < value.length && !value.charAt(i).match(/\d/)) { i++; }
		}
		if(options.seconds) {
			while(i < value.length && value.charAt(i).match(/\d{1}/)) {
				s += value.charAt(i);
				i++;
			}
			while(i < value.length && !value.charAt(i).match(/\d/)) { i++; }
		}
		h = parseInt(h,10);
		m = parseInt(m,10);
		s = parseInt(s,10);
		if(!h && h !== 0) { h = false; }
		if(!m && m !== 0) { m = false; }
		if(!s && s !== 0) { s = false; }
		if(!options.calculate) {
			if(h > 23) { h = false; }
			if(m > 59) { m = false; }
			if(s > 59) { s = false; }
		}
		else {
			if(h > 99) { h = false; }
			if(m > 99) { m = false; }
			if(s > 99) { s = false; }
		}
		if(h === false) { h = last_value ? last_value.getHours() : 0; }
		if(m === false) { m = last_value ? last_value.getMinutes() : 0; }
		if(s === false) { s = last_value ? last_value.getSeconds() : 0; }

		if(options.ampm && options.hours) {
			if(value.match(/pm$/i) && h < 12) {
				h += 12;
			}
		}
		d.setHours(h);
		d.setMinutes(m);
		d.setSeconds(s);
		d.setMilliseconds(0);
		return d;
	};
	$.tmpckr.format = function (options, date) {
		var h = date.getHours(),
			m = date.getMinutes(),
			s = date.getSeconds(),
			a = 'am',
			v = '';
		if(options.ampm && h >= 12) {
			a = 'pm';
		}
		if(options.ampm && h > 12) {
			h -= 12;
		}
		if(options.hours)   { v += ('00' + h).slice(-2); }
		if(options.minutes) { v += (options.hours ? ':' : '') + ('00' + m).slice(-2); }
		if(options.seconds) { v += (options.hours || options.minutes ? ':' : '') + ('00' + s).slice(-2); }
		if(options.ampm)    { v += ' ' + a; }
		return v;
	};
}(jQuery));

(function ($) {
	"use strict";
	if(!$.vakata) {
		$.vakata = {};
	}
	$.vakata.selection = {
		get : function (as_text) {
			if(window.getSelection) {
				if(as_text) {
					return window.getSelection().toString();
				}
				var userSelection	= window.getSelection(),
					range			= userSelection.getRangeAt && userSelection.rangeCount ? userSelection.getRangeAt(0) : document.createRange(),
					div				= document.createElement('div');
				if(!userSelection.getRangeAt) {
					range.setStart(userSelection.anchorNode, userSelection.anchorOffset);
					range.setEnd(userSelection.focusNode, userSelection.focusOffset);
				}
				div.appendChild(range.cloneContents());
				return div.innerHTML;
			}
			if(document.selection) {
				return document.selection.createRange()[ as_text ? 'text' : 'htmlText' ];
			}
			return '';
		},
		elm_get : function (e) {
			e = typeof e === 'string' ? document.getElementById(e) : e;
			if(e.jquery) { e = e.get(0); }
			if(e.setSelectionRange) { // Mozilla and DOM 3.0
				return {
					'start'		: e.selectionStart,
					'end'		: e.selectionEnd,
					'length'	: (e.selectionEnd - e.selectionStart),
					'text'		: e.value.substr(e.selectionStart, (e.selectionEnd - e.selectionStart))
				};
			}
			if(document.selection) { // IE
				e.focus();
				var tr0 = document.selection.createRange(),
					tr1 = false,
					tr2 = false,
					len, text_whole, the_start, the_end;
				if(tr0 && tr0.parentElement() === e) {
					len = e.value.length;
					text_whole = e.value.replace(/\r\n/g, "\n");

					tr1 = e.createTextRange();
					tr1.moveToBookmark(tr0.getBookmark());
					tr2 = e.createTextRange();
					tr2.collapse(false);

					if(tr1.compareEndPoints("StartToEnd", tr2) > -1) {
						the_start = the_end = len;
					}
					else {
						the_start  = -tr1.moveStart("character", -len);
						the_start += text_whole.slice(0, the_start).split("\n").length - 1;
						if (tr1.compareEndPoints("EndToEnd", tr2) > -1) {
							the_end = len;
						} else {
							the_end  = -tr1.moveEnd("character", -len);
							the_end += text_whole.slice(0, the_end).split("\n").length - 1;
						}
					}
					text_whole = e.value.slice(the_start, the_end);
					return {
						'start'		: the_start,
						'end'		: the_end,
						'length'	: text_whole.length,
						'text'		: text_whole
					};
				}
			}
			else { // not supported
				return {
					'start'		: e.value.length,
					'end'		: e.value.length,
					'length'	: 0,
					'text'		: ''
				};
			}
		},
		elm_set : function (e, beg, end) {
			e = typeof e === 'string' ? document.getElementById(e) : e;
			if(e.jquery) { e = e.get(0); }
			if(e.setSelectionRange) { // Mozilla and DOM 3.0
				e.focus();
				e.setSelectionRange(beg, end);
			}
			else if(document.selection) { // IE
				e.focus();
				var tr	= e.createTextRange(),
					tx	= e.value.replace(/\r\n/g, "\n");

				beg -= tx.slice(0, beg).split("\n").length - 1;
				end -= tx.slice(0, end).split("\n").length - 1;

				tr.collapse(true);
				tr.moveEnd('character', end);
				tr.moveStart('character', beg);
				tr.select();
			}
			return $.vakata.selection.elm_get(e);
		},
		elm_replace : function (e, replace) {
			e = typeof e === 'string' ? document.getElementById(e) : e;
			if(e.jquery) { e = e.get(0); }
			var sel = $.vakata.selection.elm_get(e),
				beg = sel.start,
				end = beg + replace.length;
			e.value = e.value.substr(0, beg) + replace + e.value.substr(sel.end, e.value.length);
			$.vakata.selection.elm_set(e, beg, end);
			return {
				'start'		: beg,
				'end'		: end,
				'length'	: replace.length,
				'text'		: replace
			};
		},
		elm_get_caret : function (e) {
			return $.vakata.selection.elm_get(e).end;
		},
		elm_set_caret : function (e, pos) {
			return $.vakata.selection.elm_set(e, pos, pos);
		},
		elm_get_caret_position : function (e) {
			e = typeof e === 'string' ? document.getElementById(e) : e;
			if(e.jquery) { e = e.get(0); }
			var p = $.vakata.selection.elm_get_caret(e),
				s = e.value.substring(0, p).replace(/&/g,'&amp;').replace(/</ig,'&lt;').replace(/>/ig,'&gt;').replace(/\r/g, '').replace(/\t/g,'&#10;').replace(/\n/ig, '<br />'),
				b = $.vakata.get_scrollbar_width(),
				w = $(e).width(),
				h = $(e).height();
			if(e.scrollHeight > h) { w -= b; }
			if(e.scrollWidth > w)  { h -= b; }
			e = $(e);
			e = $('<div />').html(s).css({
						'background': 'red',
						'width'		: w + 'px',
						'height'	: 'auto',
						'position'	: 'absolute',
						'left'		: '0px',
						'top'		: '-10000px',

						'fontSize'		: e.css('fontSize'),
						'fontFamily'	: e.css('fontFamily'),
						'fontWeight'	: e.css('fontWeight'),
						'fontVariant'	: e.css('fontVariant'),
						'fontStyle'		: e.css('fontStyle'),
						'textTransform'	: e.css('textTransform'),
						'lineHeight'	: e.css('lineHeight'),
						'whiteSpace'	: 'pre-wrap'
					});
			e.append('<span class="caret">&nbsp;</span>').appendTo('body');
			s = e.find('span.caret');
			p = s.offset();
			p.top = p.top + 10000 + s.height();
			e.remove();
			return p;
		}
	};
}(jQuery));

(function ($) {
	"use strict";
	var sb;
	$.vakata.get_scrollbar_width = function () {
		var e1, e2;
		if(!sb) {
			if(/msie/.test(navigator.userAgent.toLowerCase())) {
				e1 = $('<textarea cols="10" rows="2"></textarea>').css({ position: 'absolute', top: -1000, left: 0 }).appendTo('body');
				e2 = $('<textarea cols="10" rows="2"></textarea>').css({ overflow: 'hidden', position: 'absolute', top: -1000, left: 0 }).appendTo('body');
				sb = e1.width() - e2.width();
				e1.add(e2).remove();
			}
			else {
				e1 = $('<div />').css({ width: 100, height: 100, overflow: 'auto', position: 'absolute', top: -1000, left: 0 })
						.prependTo('body').append('<div />').find('div').css({ width: '100%', height: 200 });
				sb = 100 - e1.width();
				e1.parent().remove();
			}
		}
		return sb;
	};
}(jQuery));

}));
