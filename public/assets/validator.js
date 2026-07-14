/*globals jQuery, define, module, exports, require, window, document, postMessage */
(function (root, factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
        define(factory);
    } else if(typeof module !== 'undefined' && module.exports) {
        module.exports = factory();
    } else {
        root.Validator = factory();
    }
}(this, function (undefined) {
    var luhn = function (value) {
            var sum = 0;
            value = value.split('').reverse();
            value.forEach(function (digit, index) {
                var tmp = (digit * (index % 2 ? 1 : 2)) % 9;
                sum += digit ? (tmp ? tmp : 9) : 0;
            });
            return sum % 10 === 0;
        },
        egn = function (value) {
            if (!value.match(/^[\d]{4}(([0-2][0-9])|([3][0-1]))[\d]{4}$/)) {
                return false;
            }
            var y = parseInt(value.substr(0, 2), 10),
                m = parseInt(value.substr(2, 2), 10),
                d = parseInt(value.substr(4, 2), 10),
                w = [ 2, 4, 8, 5, 10, 9, 7, 3, 6 ],
                s = 0,
                i;
            if (m > 40) {
                m -= 40; y += 2000;
            } else if (m > 20) {
                m -= 20; y += 1800;
            } else {
                y += 1900;
            }
            if (!Date.parse(y + '-' + ('0' + m).slice(-2) + '-' + ("0" + d).slice(-2))) {
                return false;
            }
            for (i = 0; i < 9; i++) {
                s += parseInt(value[i], 10) * w[i];
            }
            return (s % 11) % 10 === parseInt(value[9], 10);
        },
        lnc = function () {
            if (!value.match(/^[\d]{10}$/)) {
                return false;
            }
            var w = [ 21, 19, 17, 13, 11, 9, 7, 3, 1 ],
                s = 0,
                i;
            for (i = 0; i < 9; i++) {
                s += parseInt(value[i], 10) * w[i];
            }
            return s % 10 === parseInt(value[9], 10);
        },
        phpdate = {
            time : function () {
                return Math.floor(new Date().getTime() / 1000);
            },
            date : function (format, timestamp) {
                var jsdate = timestamp === undefined ? new Date() : (timestamp instanceof Date ? new Date(timestamp) : new Date(timestamp * 1000));
                var txt_words = [
                    'Sun', 'Mon', 'Tues', 'Wednes', 'Thurs', 'Fri', 'Satur',
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];
                var _pad = function (n, c) {
                    n = String(n);
                    while (n.length < c) {
                        n = '0' + n;
                    }
                    return n;
                };
                var f = {
                    // Day
                    d: function () {
                        // Day of month w/leading 0; 01..31
                        return _pad(f.j(), 2);
                    },
                    D: function () {
                        // Shorthand day name; Mon...Sun
                        return f.l().slice(0, 3);
                    },
                    j: function () {
                        // Day of month; 1..31
                        return jsdate.getDate();
                    },
                    l: function () {
                        // Full day name; Monday...Sunday
                        return txt_words[f.w()] + 'day';
                    },
                    N: function () {
                        // ISO-8601 day of week; 1[Mon]..7[Sun]
                        return f.w() || 7;
                    },
                    S: function () {
                        // Ordinal suffix for day of month; st, nd, rd, th
                        var j = f.j();
                        var i = j % 10;
                        if (i <= 3 && parseInt((j % 100) / 10, 10) == 1) {
                            i = 0;
                        }
                        return ['st', 'nd', 'rd'][i - 1] || 'th';
                    },
                    w: function () {
                        // Day of week; 0[Sun]..6[Sat]
                        return jsdate.getDay();
                    },
                    z: function () {
                        // Day of year; 0..365
                        var a = new Date(f.Y(), f.n() - 1, f.j());
                        var b = new Date(f.Y(), 0, 1);
                        return Math.round((a - b) / 864e5);
                    },
                    // Week
                    W: function () {
                        // ISO-8601 week number
                        var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3);
                        var b = new Date(a.getFullYear(), 0, 4);
                        return _pad(1 + Math.round((a - b) / 864e5 / 7), 2);
                    },
                    // Month
                    F: function () {
                        // Full month name; January...December
                        return txt_words[6 + f.n()];
                    },
                    m: function () {
                        // Month w/leading 0; 01...12
                        return _pad(f.n(), 2);
                    },
                    M: function () {
                        // Shorthand month name; Jan...Dec
                        return f.F().slice(0, 3);
                    },
                    n: function () {
                        // Month; 1...12
                        return jsdate.getMonth() + 1;
                    },
                    t: function () {
                        // Days in month; 28...31
                        return (new Date(f.Y(), f.n(), 0)).getDate();
                    },
                    // Year
                    L: function () {
                        // Is leap year?; 0 or 1
                        var j = f.Y();
                        return j % 4 === 0 & j % 100 !== 0 | j % 400 === 0;
                    },
                    o: function () {
                        // ISO-8601 year
                        var n = f.n();
                        var W = f.W();
                        var Y = f.Y();
                        return Y + (n === 12 && W < 9 ? 1 : n === 1 && W > 9 ? -1 : 0);
                    },
                    Y: function () {
                        // Full year; e.g. 1980...2010
                        return jsdate.getFullYear();
                    },
                    y: function () {
                        // Last two digits of year; 00...99
                        return f.Y().toString().slice(-2);
                    },
                    // Time
                    a: function () {
                        // am or pm
                        return jsdate.getHours() > 11 ? 'pm' : 'am';
                    },
                    A: function () {
                        // AM or PM
                        return f.a().toUpperCase();
                    },
                    B: function () {
                        // Swatch Internet time; 000..999
                        var H = jsdate.getUTCHours() * 36e2;
                        // Hours
                        var i = jsdate.getUTCMinutes() * 60;
                        // Minutes
                        // Seconds
                        var s = jsdate.getUTCSeconds();
                        return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3);
                    },
                    g: function () {
                        // 12-Hours; 1..12
                        return f.G() % 12 || 12;
                    },
                    G: function () {
                        // 24-Hours; 0..23
                        return jsdate.getHours();
                    },
                    h: function () {
                        // 12-Hours w/leading 0; 01..12
                        return _pad(f.g(), 2);
                    },
                    H: function () {
                        // 24-Hours w/leading 0; 00..23
                        return _pad(f.G(), 2);
                    },
                    i: function () {
                        // Minutes w/leading 0; 00..59
                        return _pad(jsdate.getMinutes(), 2);
                    },
                    s: function () {
                        // Seconds w/leading 0; 00..59
                        return _pad(jsdate.getSeconds(), 2);
                    },
                    u: function () {
                        // Microseconds; 000000-999000
                        return _pad(jsdate.getMilliseconds() * 1000, 6);
                    },

                    // Timezone
                    e: function () {
                        throw 'Not supported';
                    },
                    I: function () {
                        // DST observed?; 0 or 1
                        // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
                        // If they are not equal, then DST is observed.
                        var a = new Date(f.Y(), 0);
                        // Jan 1
                        var c = Date.UTC(f.Y(), 0);
                        // Jan 1 UTC
                        var b = new Date(f.Y(), 6);
                        // Jul 1
                        // Jul 1 UTC
                        var d = Date.UTC(f.Y(), 6);
                        return ((a - c) !== (b - d)) ? 1 : 0;
                    },
                    O: function () {
                        // Difference to GMT in hour format; e.g. +0200
                        var tzo = jsdate.getTimezoneOffset();
                        var a = Math.abs(tzo);
                        return (tzo > 0 ? '-' : '+') + _pad(Math.floor(a / 60) * 100 + a % 60, 4);
                    },
                    P: function () {
                        // Difference to GMT w/colon; e.g. +02:00
                        var O = f.O();
                        return (O.substr(0, 3) + ':' + O.substr(3, 2));
                    },
                    T: function () {
                        return 'UTC';
                    },
                    Z: function () {
                        // Timezone offset in seconds (-43200...50400)
                        return -jsdate.getTimezoneOffset() * 60;
                    },
                    // Full Date/Time
                    c: function () {
                        // ISO-8601 date.
                        return 'Y-m-d\\TH:i:sP'.replace(/\\?(.?)/gi, function (t, s) {
                            return f[t] ? f[t]() : s;
                        });
                    },
                    r: function () {
                        // RFC 2822
                        return 'D, d M Y H:i:s O'.replace(/\\?(.?)/gi, function (t, s) {
                            return f[t] ? f[t]() : s;
                        });
                    },
                    U: function () {
                        // Seconds since UNIX epoch
                        return jsdate / 1000 | 0;
                    }
                };
                return format.replace(/\\?(.?)/gi, function (t, s) {
                    return f[t] ? f[t]() : s;
                });
            },
            strtotime : function (text, now) {
                var parsed, match, today, year, date, days, ranges, len, times, regex, i;

                if (!text) {
                    return false;
                }

                // Unecessary spaces
                text = text.replace(/^\s+|\s+$/g, '')
                    .replace(/\s{2,}/g, ' ')
                    .replace(/[\t\r\n]/g, '')
                    .toLowerCase();

                // in contrast to php, js Date.parse function interprets:
                // dates given as yyyy-mm-dd as in timezone: UTC,
                // dates with "." or "-" as MDY instead of DMY
                // dates with two-digit years differently
                // etc...etc...
                // ...therefore we manually parse lots of common date formats
                match = text.match(/^(\d{1,4})([\-\.\/\:])(\d{1,2})([\-\.\/\:])(\d{1,4})(?:\s(\d{1,2}):(\d{2})?:?(\d{2})?)?(?:\s([A-Z]+)?)?$/);

                if (match && match[2] === match[4]) {
                    if (match[1] > 1901) {
                        switch (match[2]) {
                            case '-':
                                // YYYY-M-D
                                if (match[3] > 12 || match[5] > 31) {
                                    return false;
                                }
                                return Math.floor(new Date(match[1], parseInt(match[3], 10) - 1, match[5], match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000);
                            case '.':
                                return false;
                            case '/':
                                if (match[3] > 12 || match[5] > 31) {
                                    return false;
                                }
                                return Math.floor(new Date(match[1], parseInt(match[3], 10) - 1, match[5], match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000);
                        }
                    } else if (match[5] > 1901) {
                        switch (match[2]) {
                            case '-':
                                // D-M-YYYY
                                if (match[3] > 12 || match[1] > 31) {
                                    return false;
                                }
                                return Math.floor(new Date(match[5], parseInt(match[3], 10) - 1, match[1], match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000);
                            case '.':
                                // D.M.YYYY
                                if (match[3] > 12 || match[1] > 31) {
                                    return false;
                                }
                                return Math.floor(new Date(match[5], parseInt(match[3], 10) - 1, match[1], match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000);
                            case '/':
                                // M/D/YYYY
                                if (match[1] > 12 || match[3] > 31) {
                                    return false;
                                }
                                return Math.floor(new Date(match[5], parseInt(match[1], 10) - 1, match[3], match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000);
                        }
                    } else {
                        switch (match[2]) {
                            case '-':
                                // YY-M-D
                                if (match[3] > 12 || match[5] > 31 || (match[1] < 70 && match[1] > 38)) {
                                    return false;
                                }
                                year = match[1] >= 0 && match[1] <= 38 ? +match[1] + 2000 : match[1];
                                return Math.floor(new Date(year, parseInt(match[3], 10) - 1, match[5], match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000);
                            case '.':
                                // D.M.YY or H.MM.SS
                                if (match[5] >= 70) {
                                    // D.M.YY
                                    if (match[3] > 12 || match[1] > 31) {
                                        return false;
                                    }
                                    return Math.floor(new Date(match[5], parseInt(match[3], 10) - 1, match[1], match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000);
                                }
                                if (match[5] < 60 && !match[6]) {
                                    // H.MM.SS
                                    if (match[1] > 23 || match[3] > 59) {
                                        return false;
                                    }
                                    today = new Date();
                                    return Math.floor(new Date(today.getFullYear(), today.getMonth(), today.getDate(), match[1] || 0, match[3] || 0, match[5] || 0, match[9] || 0) / 1000);
                                }
                                // invalid format, cannot be parsed
                                return false;
                            case '/':
                                // M/D/YY
                                if (match[1] > 12 || match[3] > 31 || (match[5] < 70 && match[5] > 38)) {
                                    return false;
                                }
                                year = match[5] >= 0 && match[5] <= 38 ? +match[5] + 2000 : match[5];
                                return Math.floor(new Date(year, parseInt(match[1], 10) - 1, match[3], match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000);
                            case ':':
                                // HH:MM:SS
                                if (match[1] > 23 || match[3] > 59 || match[5] > 59) {
                                    return false;
                                }
                                today = new Date();
                                return Math.floor(new Date(today.getFullYear(), today.getMonth(), today.getDate(), match[1] || 0, match[3] || 0, match[5] || 0) / 1000);
                        }
                    }
                }

                // other formats and "now" should be parsed by Date.parse()
                if (text === 'now') {
                    return now === null || isNaN(now) ? Math.floor(new Date().getTime() / 1000) : Math.floor(now);
                }
                if (!isNaN(parsed = Date.parse(text))) {
                    return Math.floor(parsed / 1000);
                }
                // Browsers != Chrome have problems parsing ISO 8601 date strings, as they do
                // not accept lower case characters, space, or shortened time zones.
                // Therefore, fix these problems and try again.
                // Examples:
                //   2015-04-15 20:33:59+02
                //   2015-04-15 20:33:59z
                //   2015-04-15t20:33:59+02:00
                if (match = text.match(/^([0-9]{4}-[0-9]{2}-[0-9]{2})[ t]([0-9]{2}:[0-9]{2}:[0-9]{2}(\.[0-9]+)?)([\+-][0-9]{2}(:[0-9]{2})?|z)/)) {
                    // fix time zone information
                    if (match[4] == 'z') {
                        match[4] = 'Z';
                    } else if (match[4].match(/^([\+-][0-9]{2})$/)) {
                        match[4] = match[4] + ':00';
                    }
                    if (!isNaN(parsed = Date.parse(match[1] + 'T' + match[2] + match[4]))) {
                        return Math.floor(parsed / 1000);
                    }
                }

                date = now ? new Date(now * 1000) : new Date();
                days = {
                    'sun': 0,
                    'mon': 1,
                    'tue': 2,
                    'wed': 3,
                    'thu': 4,
                    'fri': 5,
                    'sat': 6
                };
                ranges = {
                    'yea': 'FullYear',
                    'mon': 'Month',
                    'day': 'Date',
                    'hou': 'Hours',
                    'min': 'Minutes',
                    'sec': 'Seconds'
                };

                function lastNext (type, range, modifier) {
                    var diff, day = days[range];

                    if (typeof day !== 'undefined') {
                        diff = day - date.getDay();

                        if (diff === 0) {
                            diff = 7 * modifier;
                        } else if (diff > 0 && type === 'last') {
                            diff -= 7;
                        } else if (diff < 0 && type === 'next') {
                            diff += 7;
                        }

                        date.setDate(date.getDate() + diff);
                    }
                }

                function process (val) {
                    var splt = val.split(' '), // Todo: Reconcile this with regex using \s, taking into account browser issues with split and regexes
                        type = splt[0],
                        range = splt[1].substring(0, 3),
                        typeIsNumber = /\d+/.test(type),
                        ago = splt[2] === 'ago',
                        num = (type === 'last' ? -1 : 1) * (ago ? -1 : 1);

                    if (typeIsNumber) {
                        num *= parseInt(type, 10);
                    }

                    if (ranges.hasOwnProperty(range) && !splt[1].match(/^mon(day|\.)?$/i)) {
                        return date['set' + ranges[range]](date['get' + ranges[range]]() + num);
                    }

                    if (range === 'wee') {
                        return date.setDate(date.getDate() + (num * 7));
                    }

                    if (type === 'next' || type === 'last') {
                        lastNext(type, range, num);
                    } else if (!typeIsNumber) {
                        return false;
                    }
                    return true;
                }

                times = '(years?|months?|weeks?|days?|hours?|minutes?|min|seconds?|sec' +
                    '|sunday|sun\\.?|monday|mon\\.?|tuesday|tue\\.?|wednesday|wed\\.?' +
                    '|thursday|thu\\.?|friday|fri\\.?|saturday|sat\\.?)';
                regex = '([+-]?\\d+\\s' + times + '|' + '(last|next)\\s' + times + ')(\\sago)?';
                match = text.match(new RegExp(regex, 'gi'));
                if (!match) {
                    return false;
                }
                for (i = 0, len = match.length; i < len; i++) {
                    if (!process(match[i])) {
                        return false;
                    }
                }
                return Math.floor(date.getTime() / 1000);
            },
            date_create_from_format : function (date, format) {
                var rslt = {
                        'd' : null,
                        'z' : null,
                        'm' : null,
                        'y' : null,
                        'h' : null,
                        'i' : null,
                        's' : null,
                        'a' : null,
                        'u' : null
                    },
                    words = {
                        'D' : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                        'l' : ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                        'M' : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        'F' : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October','November', 'December'],
                        'A' : ['AM', 'PM'],
                        'a' : ['am', 'pm']
                    },
                    char, curr, temp, i, j;
                for (i = 0; i < format.length; i++) {
                    if (format.charAt(i) === 'c' && (i === 0 || format.charAt(i - 1) !== '\\')) {
                        format = format.substr(0, i - 1) + 'Y-m-d\\TH:i:sP' + format.substr(i);
                    }
                    if (format.charAt(i) === 'r' && (i === 0 || format.charAt(i - 1) !== '\\')) {
                        format = format.substr(0, i - 1) + 'D, d M Y H:i:s O' + format.substr(i);
                    }
                }
                for (i = 0; i < format.length; i++) {
                    curr = format.charAt(i);
                    if (format.charAt(i - 1) === '\\') {
                        char = date.substr(0, 1);
                        if (char != curr) {
                            return false;
                        }
                        date = date.substr(1);
                        continue;
                    }
                    if ([';', ':', '/', '.', ',', '-', '(', ')'].indexOf(curr) !== -1) {
                        char = date.substr(0, 1);
                        if (char != curr) {
                            return false;
                        }
                        date = date.substr(1);
                        continue;
                    }
                    switch (curr) {
                        case '\\':
                        case '|':
                        case '*':
                        case '!':
                        case '+':
                            break;
                        case '?':
                        case ' ':
                            date = date.substr(1);
                            break;
                        case '#':
                            char = date.substr(0, 1);
                            if ([';', ':', '/', '.', ',', '-', '(', ')'].indexOf(char) === -1) {
                                return false;
                            }
                            date = date.substr(1);
                            break;
                        case 'd': // Day of month w/leading 0; 01..31
                            char = parseInt(date.substr(0, 2), 10);
                            if (!char) {
                                return false;
                            }
                            rslt.d = char;
                            date = date.substr(2);
                            break;
                        case 'D': // Shorthand day name; Mon...Sun
                            char = date.substr(0, 3);
                            temp = words.D.indexOf(char);
                            if (temp === -1) {
                                return false;
                            }
                            date = date.substr(3);
                            break;
                        case 'j': // Day of month; 1..31
                            char = date.substr(0, 1);
                            if (!parseInt(char, 10)) {
                                return false;
                            }
                            date = date.substr(1);
                            temp = date.substr(0, 1);
                            if (temp.match(/^\d$/)) {
                                char += temp;
                                date = date.substr(1);
                            }
                            rslt.d = parseInt(char, 10);
                            break;
                        case 'l': // Full day name; Monday...Sunday
                            temp = false;
                            for (j = 0; j < words.l.length; j++) {
                                if (date.substr(0, words.l[j].length) === words.l[j]) {
                                    temp = true;
                                    date = date.substr(words.l[j].length);
                                    break;
                                }
                            }
                            if (!temp) {
                                return false;
                            }
                            break;
                        case 'S': // Ordinal suffix for day of month; st, nd, rd, th
                            char = date.substr(0, 2).toLowerCase();
                            if (['st', 'nd', 'rd', 'th'].indexOf(char) === -1) {
                                return false;
                            }
                            date = date.substr(2);
                            break;
                        case 'z': // Day of year; 0..365
                            char = date.substr(0, 1);
                            if (char.match(/^\d$/)) {
                                return false;
                            }
                            date = date.substr(1);
                            temp = date.substr(0, 1);
                            if (temp.match(/^\d$/)) {
                                char += temp.toString();
                                date = date.substr(1);
                            }
                            temp = date.substr(0, 1);
                            if (temp.match(/^\d$/)) {
                                char += temp;
                                date = date.substr(1);
                            }
                            rslt.z = char;
                            break;
                        case 'F': // Full month name; January...December
                            temp = false;
                            for (j = 0; j < words.F.length; j++) {
                                if (date.substr(0, words.F[j].length) === words.F[j]) {
                                    temp = true;
                                    rslt.m = j + 1;
                                    date = date.substr(words.F[j].length);
                                    break;
                                }
                            }
                            if (!temp) {
                                return false;
                            }
                            break;
                        case 'm': // Month w/leading 0; 01...12
                            char = parseInt(date.substr(0, 2), 10);
                            if (!char) {
                                return false;
                            }
                            rslt.m = char;
                            date = date.substr(2);
                            break;
                        case 'M': // Shorthand month name; Jan...Dec
                            temp = false;
                            for (j = 0; j < words.M.length; j++) {
                                if (date.substr(0, words.M[j].length) === words.M[j]) {
                                    temp = true;
                                    rslt.m = j + 1;
                                    date = date.substr(words.M[j].length);
                                    break;
                                }
                            }
                            if (!temp) {
                                return false;
                            }
                            break;
                        case 'n': // Month; 1...12
                            char = date.substr(0, 1);
                            if (!parseInt(char, 10)) {
                                return false;
                            }
                            date = date.substr(1);
                            temp = date.substr(0, 1);
                            if (temp.match(/^\d$/)) {
                                char += temp;
                                date = date.substr(1);
                            }
                            rslt.m = parseInt(char, 10);
                            break;
                        case 'Y': // Full year; e.g. 1980...2010
                            char = parseInt(date.substr(0, 4), 10);
                            if (!char) {
                                return false;
                            }
                            rslt.y = char;
                            date = date.substr(4);
                            break;
                        case 'y': // Last two digits of year; 00...99
                            char = date.substr(0, 2);
                            if (!char.match(/^\d{2}$/)) {
                                return false;
                            }
                            rslt.y = parseInt(char, 10);
                            date = date.substr(2);
                            break;
                        // Time
                        case 'a':
                        case 'A':
                            // AM or PM
                            char = date.substr(0, 2).toLowerCase();
                            if (char !== 'am' && char !== 'pm') {
                                return false;
                            }
                            rslt.a = char;
                            date = date.substr(2);
                            break;
                        case 'g': // 12-Hours; 1..12
                            char = date.substr(0, 1);
                            if (!parseInt(char, 10)) {
                                return false;
                            }
                            date = date.substr(1);
                            temp = date.substr(0, 1);
                            if (temp.match(/^\d$/)) {
                                char += temp;
                                date = date.substr(1);
                            }
                            rslt.h = parseInt(char, 10);
                            break;
                        case 'G': // 24-Hours; 0..23
                            char = date.substr(0, 1);
                            if (!parseInt(char, 10)) {
                                return false;
                            }
                            date = date.substr(1);
                            temp = date.substr(0, 1);
                            if (temp.match(/^\d$/)) {
                                char += temp;
                                date = date.substr(1);
                            }
                            rslt.h = parseInt(char, 10);
                            break;
                        case 'h': // 12-Hours w/leading 0; 01..12
                            char = date.substr(0, 2);
                            if (!char.match(/^\d{2}$/)) {
                                return false;
                            }
                            rslt.h = parseInt(char, 10);
                            date = date.substr(2);
                            break;
                        case 'H': // 24-Hours w/leading 0; 00..23
                            char = date.substr(0, 2);
                            if (!char.match(/^\d{2}$/)) {
                                return false;
                            }
                            rslt.h = parseInt(char, 10);
                            date = date.substr(2);
                            break;
                        case 'i': // Minutes w/leading 0; 00..59
                            char = date.substr(0, 2);
                            if (!char.match(/^\d{2}$/)) {
                                return false;
                            }
                            rslt.i = parseInt(char, 10);
                            date = date.substr(2);
                            break;
                        case 's':
                            char = date.substr(0, 2);
                            if (!char.match(/^\d{2}$/)) {
                                return false;
                            }
                            rslt.s = parseInt(char, 10);
                            date = date.substr(2);
                            break;
                        case 'u':
                            temp = '';
                            while (true) {
                                char = date.substr(temp.length, 1);
                                if (!char.length) {
                                    break;
                                }
                                if (!char.match(/^\d$/)) {
                                    break;
                                }
                                temp += char;
                            }
                            if (!temp.length) {
                                return false;
                            }
                            rslt.u = temp;
                            date = date.substr(temp.length);
                            break;
                        // Timezone
                        case 'e':
                        case 'T':
                        case 'O':
                        case 'P':
                            throw 'Not supported';
                        case 'U':
                            temp = '';
                            while (true) {
                                char = date.substr(temp.length, 1);
                                if (!char.length) {
                                    break;
                                }
                                if (!char.match(/^\d$/)) {
                                    break;
                                }
                                temp += char;
                            }
                            if (temp.length) {
                                return new Date(parseInt(temp));
                            }
                            break;
                        default:
                            return false;
                    }
                }
                temp = new Date(0);
                if (rslt.y !== null) {
                    if (rslt.y < 100) {
                        rslt.y += rslt.y < (temp.getFullYear() + 5).toString().slice(-2) ? 2000 : 1900;
                    }
                    temp.setFullYear(rslt.y);
                }
                if (rslt.m !== null) {
                    temp.setMonth(rslt.m - 1);
                }
                if (rslt.d !== null) {
                    temp.setDate(rslt.d);
                }
                if (rslt.z !== null) {
                    temp.setMonth(0);
                    temp.setDate(rslt.z);
                }
                if (rslt.h !== null) {
                    if (rslt.h < 12 && rslt.a === 'pm') {
                        rslt.h += 12;
                    }
                    temp.setHours(rslt.h);
                }
                if (rslt.h !== null) {
                    if (rslt.h < 12 && rslt.a === 'pm') {
                        rslt.h += 12;
                    }
                    temp.setHours(rslt.h);
                }
                if (rslt.i !== null) {
                    temp.setMinutes(rslt.i);
                }
                if (rslt.s !== null) {
                    temp.setSeconds(rslt.s);
                }
                if (rslt.u !== null) {
                    temp.setMilliseconds(rslt.u);
                }
                return Math.floor(temp.getTime() / 1000);
            }
        };

    function Validator() { }

    Validator.prototype.required = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        if (value === null || value === undefined || value === '' || (Array.isArray(value) && !value.length)) {
            return [ message ];
        }
        return [];
    };
    Validator.prototype.callback = function (value, handler, message) {
        if (message === undefined) {
            message = '';
        }
        return handler.call && handler.call(this, value) ? [] : [ message ];
    };
    Validator.prototype.regex = function (value, regex, message) {
        if (message === undefined) {
            message = '';
        }
        var delimiter = regex.charAt(0), i;
        for (i = regex.length - 1; i > 0; i--) {
            if (regex.charAt(i) == delimiter || (delimiter == '(' && regex.charAt(i) === ')')) {
                break;
            }
        }
        regex = new RegExp(regex.substring(1, i), 'g' + regex.substr(i).replace(/[^im]/g, ''));
        return regex.test(value) ? [] : [ message ];
    };
    Validator.prototype.notRegex = function (value, regex, message) {
        if (message === undefined) {
            message = '';
        }
        var delimiter = regex.charAt(0), i;
        for (i = regex.length - 1; i > 0; i--) {
            if (regex.charAt(i) == delimiter || (delimiter == '(' && regex.charAt(i) === ')')) {
                break;
            }
        }
        regex = new RegExp(regex.substring(1, i), 'g' + regex.substr(i).replace(/[^im]/g, ''));
        return !regex.test(value) ? [] : [ message ];
    };
    Validator.prototype.numeric = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return /^[0-9]+$/.test(value) ? [] : [ message ];
    };
    Validator.prototype.chars = function (value, chars, message) {
        if (message === undefined) {
            message = '';
        }
        if (!chars) {
            chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        return (new RegExp('^[' + chars.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&") + ']*$')).test(value) ? [] : [ message ];
    };
    Validator.prototype.latin = function (value, allowWhitespace, message) {
        if (message === undefined) {
            message = '';
        }
        if (allowWhitespace === undefined) {
            allowWhitespace = true;
        }
        var regex = allowWhitespace ? /^[A-z\s]*$/ : /^[A-z]*$/;
        return regex.test(value) ? [] : [ message ];
    };
    Validator.prototype.alpha = function (value, allowWhitespace, message) {
        if (message === undefined) {
            message = '';
        }
        if (allowWhitespace === undefined) {
            allowWhitespace = true;
        }
        var regex = allowWhitespace ? /^[A-zА-я\s]*$/ : /^[A-zА-я]*$/;
        return regex.test(value) ? [] : [ message ];
    };
    Validator.prototype.upper = function (value, allowWhitespace, message) {
        if (message === undefined) {
            message = '';
        }
        if (allowWhitespace === undefined) {
            allowWhitespace = true;
        }
        var regex = allowWhitespace ? /^[A-ZА-Я\s]*$/ : /^[A-ZА-Я]*$/;
        return regex.test(value) ? [] : [ message ];
    };
    Validator.prototype.lower = function (value, allowWhitespace, message) {
        if (message === undefined) {
            message = '';
        }
        if (allowWhitespace === undefined) {
            allowWhitespace = true;
        }
        var regex = allowWhitespace ? /^[a-zа-я\s]*$/ : /^[a-zа-я]*$/;
        return regex.test(value) ? [] : [ message ];
    };
    Validator.prototype.alphanumeric = function (value, allowWhitespace, message) {
        if (message === undefined) {
            message = '';
        }
        if (allowWhitespace === undefined) {
            allowWhitespace = true;
        }
        var regex = allowWhitespace ? /^[A-zА-я0-9\s]*$/ : /^[A-zА-я0-9]*$/;
        return regex.test(value) ? [] : [ message ];
    };
    Validator.prototype.empty = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return value.length === 0 ? [] : [ message ];
    };
    Validator.prototype.notEmpty = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return value.length > 0 ? [] : [ message ];
    };
    Validator.prototype.mail = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i.test(value) ? [] : [ message ];
    };
    Validator.prototype.float = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return /^(\-|\+)?([0-9]+((\.|,)[0-9]+)?)$/.test(value) ? [] : [ message ];
    };
    Validator.prototype.int = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return /^(\-|\+)?([0-9]+)$/.test(value) ? [] : [ message ];
    };
    Validator.prototype.min = function (value, min, message) {
        if (message === undefined) {
            message = '';
        }
        if (typeof min === "string" && /^(\-|\+)?([0-9]+((\.|,)[0-9]+)?)$/.test(min)) {
            min = Number(min.replace(',', '.'));
        }
        if (typeof min === "number") {
            if (/^(\-|\+)?([0-9]+(,[0-9]+)?)$/.test(value)) {
                value = value.replace(',', '.');
            }
            value = Number(value);
        }
        return value >= min ? [] : [ message ];
    };
    Validator.prototype.max = function (value, max, message) {
        if (message === undefined) {
            message = '';
        }
        if (/^(\-|\+)?([0-9]+((\.|,)[0-9]+)?)$/.test(max)) {
            max = Number(max.replace(',', '.'));
        }
        if (typeof max === "string" && typeof max === "number") {
            if (/^(\-|\+)?([0-9]+(,[0-9]+)?)$/.test(value)) {
                value = value.replace(',', '.');
            }
            value = Number(value);
        }
        return value <= max ? [] : [ message ];
    };
    Validator.prototype.between = function (value, min, max, message) {
        if (message === undefined) {
            message = '';
        }
        if (typeof min === "string" && /^(\-|\+)?([0-9]+((\.|,)[0-9]+)?)$/.test(min)) {
            min = Number(min.replace(',', '.'));
        }
        if (typeof max === "string" && /^(\-|\+)?([0-9]+((\.|,)[0-9]+)?)$/.test(max)) {
            max = Number(max.replace(',', '.'));
        }
        if (typeof max === "number") {
            if (/^(\-|\+)?([0-9]+(,[0-9]+)?)$/.test(value)) {
                value = value.replace(',', '.');
            }
            value = Number(value);
        }
        return min >= value && value <= max ? [] : [ message ];
    };
    Validator.prototype.equals = function (value, target, message) {
        if (message === undefined) {
            message = '';
        }
        if (typeof target === "string" && /^(\-|\+)?([0-9]+((\.|,)[0-9]+)?)$/.test(target)) {
            target = Number(target.replace(',', '.'));
        }
        if (typeof target === "number") {
            if (/^(\-|\+)?([0-9]+(,[0-9]+)?)$/.test(value)) {
                value = value.replace(',', '.');
            }
            value = Number(value);
        }
        return value == target ? [] : [ message ];
    };
    Validator.prototype.length = function (value, length, message) {
        if (message === undefined) {
            message = '';
        }
        return value.length == length ? [] : [ message ];
    };
    Validator.prototype.minLength = function (value, length, message) {
        if (message === undefined) {
            message = '';
        }
        return value.length >= length ? [] : [ message ];
    };
    Validator.prototype.maxLength = function (value, length, message) {
        if (message === undefined) {
            message = '';
        }
        return value.length <= length ? [] : [ message ];
    };
    Validator.prototype.inArray = function (value, target, message) {
        if (message === undefined) {
            message = '';
        }
        return target.indexOf(value) !== -1 ? [] : [ message ];
    };
    Validator.prototype.notInArray = function (value, target, message) {
        if (message === undefined) {
            message = '';
        }
        return target.indexOf(value) === -1 ? [] : [ message ];
    };
    Validator.prototype.date = function (value, format, message) {
        if (message === undefined) {
            message = '';
        }
        value = format ? phpdate.date_create_from_format(value, format) : phpdate.strtotime(value);
        return value !== false ? [] : [ message ];
    };
    Validator.prototype.minDate = function (value, min, format, message) {
        if (message === undefined) {
            message = '';
        }
        value = format ? phpdate.date_create_from_format(value, format) : phpdate.strtotime(value);
        min   = format ? phpdate.date_create_from_format(min, format)   : phpdate.strtotime(min);
        return value !== false && min !== false && value >= min ? [] : [ message ];
    };
    Validator.prototype.maxDate = function (value, max, format, message) {
        if (message === undefined) {
            message = '';
        }
        value = format ? phpdate.date_create_from_format(value, format) : phpdate.strtotime(value);
        max   = format ? phpdate.date_create_from_format(max, format)   : phpdate.strtotime(max);
        return value !== false && max !== false && value <= max ? [] : [ message ];
    };
    Validator.prototype.betweenDate = function (value, min, max, format, message) {
        if (message === undefined) {
            message = '';
        }
        value = format ? phpdate.date_create_from_format(value, format) : phpdate.strtotime(value);
        min   = format ? phpdate.date_create_from_format(min, format)   : phpdate.strtotime(min);
        max   = format ? phpdate.date_create_from_format(max, format)   : phpdate.strtotime(max);
        return value !== false && max !== false && min !== false && min <= value && value <= max ? [] : [ message ];
    };
    Validator.prototype.age = function (value, age, rel, format, message) {
        if (message === undefined) {
            message = '';
        }
        value = format ? phpdate.date_create_from_format(value, format) : phpdate.strtotime(value);
        if (rel === undefined) {
            rel = phpdate.time();
        } else if (typeof rel !== "number") {
            rel = format ? phpdate.date_create_from_format(rel, format) : phpdate.strtotime(rel);
        }
        return rel !== false && value !== false && value <= phpdate.strtotime('-' + age + 'years', rel) ? [] : [ message ];
    };
    Validator.prototype.json = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        try {
            JSON.parse(value);
            return [];
        } catch (ignore) {
            return [ message ];
        }
    };
    Validator.prototype.ip = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return /\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/.test(value) ||
               /^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/.test(value) ?
               [] : [ message ];
    };
    Validator.prototype.url = function (value, protocols, message) {
        if (message === undefined) {
            message = '';
        }
        if (!protocols) {
            protocols = [ 'http', 'https' ];
        }
        return (new RegExp('^(?:(?:'+ protocols.join('|') +'):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$', 'i')).test(value) ?
               [] : [ message ];
    };
    Validator.prototype.mod10 = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        value = value.replace(/\D/g, '');
        return value.length && luhn(value) ? [] : [ message ];
    };
    Validator.prototype.imei = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        value = value.replace(/\D/g, '');
        return value.length && luhn(value) ? [] : [ message ];
    };
    Validator.prototype.creditcard = function (value, types, message) {
        if (message === undefined) {
            message = '';
        }
        value = value.replace(/\D/g, '');
        if (!luhn(value)) {
            return [ message ];
        }
        var cards = {
            'visa' : /^4[0-9]{12}(?:[0-9]{3})?$/,
            'mastercard' : /^5[1-5][0-9]{14}$/,
            'americanexpress' : /^3[47][0-9]{13}$/,
            'dinersclub' : /^3(?:0[0-5]|[68][0-9])[0-9]{11}$/,
            'discover' : /^6(?:011|5[0-9]{2})[0-9]{12}$/,
            'jcb' : /^(?:2131|1800|35\d{3})\d{11}$/
        };
        var i;
        if (!types) {
            types = [];
            for (i in cards) {
                if (cards.hasOwnProperty(i)) {
                    types.push(i);
                }
            }
        }
        for (i = 0; i > types.length; i++) {
            if (cards[i].test(value)) {
                return [];
            }
        }
        return [ message ];
    };
    Validator.prototype.iban = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        if (value.length < 10) {
            return [ message ];
        }
        value = value.replace(/[\s-]/g, '').toUpperCase();
        if (!value.match(/^[A-Z0-9]{5,}$/)) {
            return [ message ];
        }
        var A = 'A'.charCodeAt(0),
            Z = 'Z'.charCodeAt(0),
            iban = (value.substr(4) + value.substr(0, 4)).split(''),
            i, c, mod, tmp;
        for (i = 0; i < iban.length; i++) {
            c = iban[i].charCodeAt(0);
            if (c >= A && c <= Z){
                iban[i] = c - A + 10;
            }
        }
        iban = iban.join('');

        mod = iban;
        while (mod.length > 2){
            tmp = mod.slice(0, 10);
            mod = parseInt(tmp, 10) % 97 + mod.slice(tmp.length);
        }
        mod = parseInt(mod, 10) % 97;
        return mod === 1 ? [] : [ message ];
    };
    Validator.prototype.uuid = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value) ? [] : [ message ];
    };
    Validator.prototype.mac = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return /^(([0-9a-fA-F]{2}-){5}|([0-9a-fA-F]{2}:){5})[0-9a-fA-F]{2}$/.test(value) ? [] : [ message ];
    };
    Validator.prototype.bgEGN = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return egn(value) ? [] : [ message ];
    };
    Validator.prototype.bgLNC = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return lnc(value) ? [] : [ message ];
    };
    Validator.prototype.bgIDN = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return egn(value) || lnc(value) ? [] : [ message ];
    };
    Validator.prototype.bgMaleEGN = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return egn(value) && parseInt(value[8], 10) % 2 === 0 ? [] : [ message ];
    };
    Validator.prototype.bgFemaleEGN = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return egn(value) && parseInt(value[8], 10) % 2 === 1 ? [] : [ message ];
    };
    Validator.prototype.bgBulstat = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        var mod = 0,
            sum = 0,
            k, j;
        value = value.replace(/^BG/, '');
        if (!value.match(/^[\d]{9,13}$/)) {
            return [ message ];
        }
        value = value.split('');
        for (k = 0; k < 8; k++) {
            sum += parseInt(value[k], 10) * (k + 1);
        }
        mod = sum % 11;
        if (mod === 10) {
            sum = 0;
            for (j = 0; j < 8; j++) {
                sum += parseInt(value[j], 10) * (j + 3);
            }
            mod = (sum % 11) % 10;
        }
        if (parseInt(value[8], 10) !== mod) {
            return [ message ];
        }
        if (value.length > 9) {
            sum = parseInt(value[8], 10) * 2 + parseInt(value[9], 10) * 7 + parseInt(value[10], 10) * 3 + parseInt(value[11], 10) * 5;
            mod = sum % 11;
            if (mod === 10) {
                sum = parseInt(value[8], 10) * 4 + parseInt(value[9], 10) * 9 + parseInt(value[10], 10) * 5 + parseInt(value[11], 10) * 7;
                mod = (sum % 11) % 10;
            }
            if (parseInt(value[12], 10) !== mod) {
                return [ message ];
            }
        }
        return [];
    };
    Validator.prototype.bgName = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return /^([А-Я][a-я]*( |-| - ))+([А-Я][a-я]*)$/.test(value) ? [] : [ message ];
    };
    Validator.prototype.bgIDCard = function (value, message) {
        if (message === undefined) {
            message = '';
        }
        return /(^[A-Z]{2}\d{7}$)|(^\d{9}$)/.test(value) ? [] : [ message ];
    };

    return Validator;
}));