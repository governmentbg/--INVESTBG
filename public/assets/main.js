$(function () {
    $('.menu-toggle').click(function (e) {
        e.preventDefault();
        $(this).closest('.main-menu').toggleClass('mobile');
        localStorage.setItem('menu-mobile', $(this).closest('.main-menu').hasClass('mobile') ? '1' : '0');
    });
    if (parseInt(localStorage.getItem('menu-mobile'), 10) ||
        (window.matchMedia && !(window.matchMedia("(min-width: 41rem)")).matches)
    ) {
        $('body > .main-menu').addClass('mobile');
    }
    new PerfectScrollbar('.menu-side');
    $('.menu-side .accordion').accordion({
        exclusive : false,
        onChange : function () {
            var active = [];
            $(this).closest('.accordion').find('.active.title').each(function () {
                active.push($(this).data('title'));
            });
            localStorage.setItem('menu', JSON.stringify(active));
        }
    });
    try {
        var active = JSON.parse(localStorage.getItem('menu') || '[]');
        var current = $('.main-menu a.item.active').closest('.content').prev().data('title');
        if (current) {
            active.push(current);
        }
        $('.main-menu .accordion > .title').each(function () {
            var title = $(this).data('title');
            if (title && active.indexOf(title) !== -1) {
                $(this).addClass('active').next().addClass('active');
            }
        });
    } catch (ignore) { }
    if (window.localStorage.getItem('menu-side-scroll') !== null) {
        $('.menu-side')[0].scrollTop = window.localStorage.getItem('menu-side-scroll');
    }
    var scroll_to = null;
    $('.menu-side')[0].addEventListener('ps-scroll-y', function () {
        clearTimeout(scroll_to);
        scroll_to = setTimeout(function () {
            window.localStorage.setItem('menu-side-scroll', $('.menu-side')[0].scrollTop);
        }, 150);
    });
    $('body > .main-menu').addClass('menu-animated');

    $('.validate-form')
        .each(function () { this.noValidate = true; })
        .on('change', '[data-redraw]', function () {
            var form = $(this).closest('form');
            form.find('.dimmer').dimmer('show');
            var data = '';
            if (form.data('serialize')) {
                var tmp = [];
                var rslt = {};
                form.find(':input')
                    .filter(function () { return $(this).closest('[data-serialize]').is('form'); })
                    .filter(function (i, v) {
                        var row = $(v).closest('.json-form-row');
                        if (!row || !row.closest(form).length) {
                            return true;
                        }
                        return false;
                    })
                    .serializeArray()
                    .forEach(function (v) {
                        if (v.name.indexOf('[]') !== -1) {
                            if (!rslt[v.name.replace('[]', '')]) {
                                rslt[v.name.replace('[]', '')] = [];
                            }
                            if (v.value) {
                                rslt[v.name.replace('[]', '')].push(v.value);
                            }
                        } else {
                            rslt[v.name] = v.value;
                        }
                    });
                tmp.push((new URLSearchParams(rslt)).toString());
                form.find('[data-serialize]').each(function () {
                    var rslt = {};
                    var elm = $(this);
                    $(this)
                        .find(':input')
                        .filter(function (i, v) {
                            var row = $(v).closest('.json-form-row');
                            if (!row || !row.closest(elm).length) {
                                return true;
                            }
                            return false;
                        })
                        .serializeArray()
                        .forEach(function (v) {
                            if (v.name.indexOf('[]') !== -1) {
                                if (!rslt[v.name.replace('[]', '')]) {
                                    rslt[v.name.replace('[]', '')] = [];
                                }
                                if (v.value) {
                                    rslt[v.name.replace('[]', '')].push(v.value);
                                }
                            } else {
                                rslt[v.name] = v.value;
                            }
                        });
                    tmp.push($(this).data('serialize') + '=' + encodeURIComponent(JSON.stringify(rslt)));
                });
                data = tmp.join('&');
            } else {
                data = form.serialize();
            }
            $.post(form.data('redraw'), data)
                .done(function (data) {
                    if (form.data('serialize')) {
                        form.find('[data-serialize]').each(function () {
                            $(this).html(data[$(this).data('serialize')] || '');
                        });
                        form.trigger('redrawn');
                        return;
                    }
                    var tab = null;
                    var focused = document.activeElement || null;
                    var field = null;
                    if (focused && $(focused).is(':input')) {
                        field = $(focused).closest('.field').find(':input[name]').attr('name');
                        if (field) {
                            focused = $(focused).closest('.field').find(':input').index(focused);
                        }
                    } else {
                        field = null;
                    }
                    if (form.children('.form-tabs').length) {
                        tab = form.children('.form-tabs').children('.item-active').index();
                    }
                    form.children('.grid, .form-tabs, .form-content, .hidden-fields, .accordion').remove();
                    form.find('.dimmer').after(data);
                    if (tab > 0) {
                        form.children('.form-tabs').children('.item').eq(tab).click();
                    }
                    form.find('.ui.accordion').accordion({ exclusive : false });
                    if (field) {
                        focused = form.find('[name="'+field+'"]').closest('.field').find(':input').eq(focused || 0);
                        if (!focused) {
                            focused = form.find('[name="'+field+'"]').closest('.field').find(':input').eq(0);
                        }
                        if (focused.closest('.accordion').length) {
                            focused.closest('.accordion-content').prev().click();
                        }
                        focused.focus();
                    }
                    form.trigger('redrawn');
                })
                .always(function () {
                    form.find('.dimmer').dimmer('hide');
                })
        })
        .submit(function (e) {
            if (!$(this).hasClass('validate-form')) {
                return;
            }
            var check = function (input, rules) {
                rules = rules.slice();
                var i,
                    j,
                    tmp,
                    par,
                    inp,
                    apply,
                    value = input.val(),
                    errors = [],
                    validator = new Validator(),
                    ruleName;
                if (!Array.isArray(value)) {
                    value = [ value ];
                }
                for (i = 0; i < rules.length; i++) {
                    tmp = rules[i].data.slice();
                    tmp.push(rules[i].message);
                    apply = true;
                    if (rules[i].when) {
                        for (j in rules[i].when) {
                            if (rules[i].when.hasOwnProperty(j)) {
                                inp = j.substr(0, 1) === '.' ?
                                    input.closest('.json-form-row').find(':input[name$="['+j.substr(1)+']"]') :
                                    input.closest('.validate-form').find(':input[name="'+j+'"]');
                                if (!inp ||
                                    check(inp, rules[i].when[j]).length
                                ) {
                                    apply = false;
                                    break;
                                }
                            }
                        }
                    }
                    if (!apply) {
                        continue;
                    }
                    ruleName = rules[i].rule;
                    if (rules[i].rule.indexOf('Relation') !== -1) {
                        rel = input.closest('.validate-form').find(':input[name="'+tmp[0]+'"]');
                        if (!rel.length) {
                            errors = errors.concat([rules[i].message || '']);
                            continue;
                        } else {
                            tmp[0] = rel.val();
                            ruleName = rules[i].rule.replace('Relation', '');
                        }
                    }
                    if (ruleName === 'required' && !value.length) {
                        par = tmp.slice();
                        par.unshift('');
                        errors = errors.concat(validator[ruleName].apply(validator, par));
                    }
                    for (j = 0; j < value.length; j++) {
                        par = tmp.slice();
                        if (validator[ruleName] && (ruleName === 'required' || value[j] !== '')) {
                            par.unshift(value[j]);
                            errors = errors.concat(validator[ruleName].apply(validator, par));
                        }
                    }
                }
                return errors;
            };
            $(this).find('.tab').show();
            $(this).find('.accordion-content').show().children('.hidden').toggleClass('hidden visible');
            $(this).find('.form-tabs .item').removeClass('red').find('.red.floating.label').remove();
            $(this).find('.accordion-content').prev().find('.red.label').remove();
            $(this).removeClass('error').find('.field[data-validate]').each(function () {
                var input = $(this).find(':input[name]').eq(0);
                if (!input.length || input.prop('disabled') || input.prop('readonly')) {
                    return true;
                }
                var field = input.closest('.field').removeClass('error'),
                    rules = field.data('validate'),
                    errors = [];
                field.find('.message').remove();

                if (rules && field.is(':visible')) {
                    errors = errors.concat(check(input, rules));
                }

                if (errors.length) {
                    e.preventDefault();
                    errors = errors.filter(function (v) { return v !== ''; });
                    if (errors.length) {
                        field.append('<div class="ui error message">' + errors.join('<br />') + '</div>');
                    }
                    field.addClass('error');
                    // var tab = field.closest('.tab', field.closest('.validate-form'));
                    // if (tab.length) {
                    //     var tab_item = field.closest('.validate-form').find('.form-tabs .item').eq(tab.index());
                    //     tab_item.addClass('red');
                    //     if (!tab_item.children('.red.floating.label').length) {
                    //         tab_item.append('<div class="floating ui red label">!</div>');
                    //     }
                    // }
                }
            });
            //$(this).find('.tab').hide().eq($(this).find('.form-tabs .active').index()).show();
            $(this).find('.tab').hide().eq($(this).find('.form-tabs .active').index()).show().end().each(function () {
                if ($(this).find('.field.error').length) {
                    var tab_item = $(this).closest('.validate-form').find('.form-tabs .item').eq($(this).index());
                    tab_item.addClass('red').append('<div class="floating ui red label validation-label">!</div>');
                }
            });
            $(this).find('.accordion-content').hide()
                    .children('.visible').toggleClass('visible hidden').end()
                .filter('.active').show()
                    .children('.hidden').toggleClass('visible hidden').end()
                .end()
                .each(function () {
                    if ($(this).find('.field.error').length) {
                        $(this).prev().append('<div class="ui mini red label validation-label">!</div>');
                    }
                });
            if (e.isDefaultPrevented()) {
                var li = $(this).addClass('error').find('.field.error:visible').eq(0);
                if (li.length) {
                    $('html, body').animate({ 'scrollTop' : (li.offset().top - 90) + 'px' }, function () {
                        li.find(':input').eq(0).focus();
                    });
                }
            } else {
                $(this).find('.dimmer').dimmer('show');
            }
        });

    $('.sortable.table').tablesort();

    $('.validate-form .ui.accordion, .read-form .ui.accordion').accordion({ exclusive : false });

    // tags drag'n'drop
    var isdrg = 0,
        initx = false,
        inity = false,
        ofstx = false,
        ofsty = false,
        holdr = false,
        elmnt = false;
    $('body')
        .on('mousedown', '.tags .label', function (e) {
            elmnt = $(this);
            try {
                e.currentTarget.unselectable = "on";
                e.currentTarget.onselectstart = function () { return false; };
                if(e.currentTarget.style) { e.currentTarget.style.MozUserSelect = "none"; }
            } catch (err) { }
            holdr = false;
            initx = e.pageX;
            inity = e.pageY;
            elmnt = $(this);
            var o = elmnt.offset();
            ofstx = e.pageX - o.left;
            ofsty = e.pageY - o.top;
            isdrg = 1;
        });
    $('body')
        .on('mousemove', function (e) {
            switch (isdrg) {
                case 0:
                    return;
                case 1:
                    if(Math.abs(e.pageX - initx) > 5 || Math.abs(e.pageY - inity)) {
                        isdrg = 2;
                    }
                    break;
                case 2:
                    var targt = $(e.target).closest('.label'), i, j;
                    if(targt.length && targt[0] !== elmnt[0] && targt.closest('.tags').length && targt.closest('.tags')[0] === elmnt.closest('.tags')[0]) {
                        i = targt.index();
                        j = elmnt.index();
                        if(i != j) {
                            targt[i>j?'after':'before'](elmnt);
                        }
                    }
                    break;
            }
        })
        .on('mouseup', function () {
            if (isdrg) {
                if (isdrg == 2) {
                    var opt = elmnt.closest('.tags').children('select').children('option');
                    elmnt.closest('.tags').children('.label').each(function () {
                        var val = $(this).data('value');
                        var o = opt.filter(function () {
                            return $(this).attr('value') == val;
                        }).eq(0);
                        o.appendTo(o.parent());
                    });
                }
                isdrg = 0;
                initx = false;
                inity = false;
                elmnt = false;
                holdr = false;
            }
        });

    $('.notifications-button')
        .on('click', function (e) {
            e.preventDefault();
            $.get($(this).attr('href') + '/ajax')
                .done(function (data) {
                    var nlist = $('<div class="ui relaxed selection list">');
                    $.each(data, function (k, v) {
                        nlist.append(
                            $('<a href="'+v.link+'" class="item">'+
                                '<i class="large '+(v.unread ? 'mail' : 'open envelope outline') + ' middle aligned icon"></i>'+
                                '<div class="content"><div class="header">'+v.title+'</div><div class="description"><small>'+v.sent+'</small></div></div>'+
                            '</div>')
                        );
                    })
                    $('.notifications-list').empty().append(nlist);
                });
        })
        .popup({ on: 'click', position: 'bottom right', 'popup' : $('.notifications-popup'), 'lastResort' : 'bottom right' });
    //$('.notifications-popup').css('marginRight', '-7px');

    $('.forums-button')
        .on('click', function (e) {
            e.preventDefault();
            $.get($(this).attr('href') + '/ajax')
                .done(function (data) {
                    var nlist = $('<div class="ui relaxed selection list">');
                    $.each(data, function (k, v) {
                        nlist.append(
                            $('<a href="'+v.link+'" class="item">'+
                                '<i class="large '+(v.unread ? 'orange chat' : 'chat') + ' middle aligned icon"></i>'+
                                '<div class="content"><div class="header">'+v.name+'</div><div class="description"><small>'+v.sent+'</small></div></div>'+
                            '</div>')
                        );
                    })
                    $('.forums-list').empty().append(nlist);
                });
        })
        .popup({ on: 'click', position: 'bottom right', 'popup' : $('.forums-popup'), 'lastResort' : 'bottom right' });
    //$('.forums-popup').css('marginRight', '-7px');

    var lsMF = $('.main-form[data-redraw]');
    var lsTO = null;
    var lsKey = 'local:' + window.location.pathname.trimEnd('/');
    if (lsMF && !lsMF.hasClass('no-ls')) {
        lsMF.on('change', function () {
            if (lsTO) {
                clearTimeout(lsTO);
            }
            lsTO = setTimeout(function () {
                localStorage.setItem(lsKey, lsMF.serialize());
            }, 2000)
        });
        lsMF.on('submit', function () {
            if (lsTO) {
                clearTimeout(lsTO);
            }
            localStorage.setItem(lsKey, lsMF.serialize());
        });
        if (localStorage.getItem(lsKey)) {
            if (confirm(window.fromLS)) {
                lsMF.find('.dimmer').dimmer('show');
                $.post(lsMF.data('redraw'), localStorage.getItem(lsKey))
                    .done(function (data) {
                        lsMF.children('.grid, .form-tabs, .form-content, .hidden-fields, .accordion').remove();
                        lsMF.find('.dimmer').after(data);
                        lsMF.find('.ui.accordion').accordion({ exclusive : false });
                        lsMF.trigger('redrawn');
                    })
                    .always(function () {
                        lsMF.find('.dimmer').dimmer('hide');
                    })
            } else {
                localStorage.removeItem(lsKey);
            }
        }
    }
    $('.main-table .button[href]').each(function () {
        if (window.localStorage.getItem('local:' + this.pathname.trimEnd('/'))) {
            $(this).closest('tr').addClass('blue');
        }
    });

    $('.main-form > .form-tabs > .item').on('click', function (e) {
        window.location.hash = $(this).index();
    });
    if (window.location.hash && window.location.hash.toString().replace(/^#/, '').match(/^\d+$/)) {
        $('.main-form > .form-tabs > .item').eq(window.location.hash.toString().substring(1)).click();
    }
});
