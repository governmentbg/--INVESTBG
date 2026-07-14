/*globals jQuery, define, module, exports, require, window, document, postMessage */
(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
        define(['jquery', 'plupload/base', 'jquery.serialize'], factory);
    }
    else if(typeof module !== 'undefined' && module.exports) {
        module.exports = factory(require('jquery'), require('plupload/base'), require('jquery.serialize'));
    }
    else {
        factory(jQuery, plupload, jQuery.serialize);
    }
}(function ($, plupload, serialize, undefined) {
    "use strict";

    if($.plupload) { return; }

    $.plupload = {};
    $.plupload.defaults = {
        runtimes     : 'html5,html4',
        url          : '/',
        chunksize    : '1mb',
        multipart    : {},
        disabled     : false,
        multiple     : false,
        count        : 0,
        images       : false,
        download     : false,
        settings     : false,
        edit         : false,
        types        : false,
        size         : 0,
        browse       : {
            clss : 'ui orange small labeled icon button',
            html : 'Прикачи',
            icon : 'ui upload icon'
        },
        zip          : {
            clss : 'ui small blue button',
            html : 'Изтегли',
            icon : 'ui download icon'
        },
        remove       : {
            clss : '',
            html : '',
            icon : 'ui close icon'
        },
        pick       : {
            clss : 'ui blue small labeled icon button',
            html : 'Избери',
            icon : 'ui folder icon'
        },
        file         : {
            done : 'ui file icon',
            wait : 'ui refresh icon',
            drag : 'ui sort icon',
            settings : 'ui setting icon',
            edit : 'ui pencil icon',
            tags : 'ui tag icon',
        },
        value        : null,
        dnd          : true,
        changed      : null,
        edited       : null
    };

    $.plupload.create = function (el, settings) {
        if(!settings) { settings = {}; }
        var options = $.extend(true, {}, $.plupload.defaults, ($(el).data('plupload') || {}), settings);
        var uploader = new plupload.Uploader({
                runtimes            : options.runtimes,
                headers             : {
                    'X-Requested-With' : 'XMLHttpRequest'
                },
                browse_button       : $(el)[0],
                multipart           : true,
                multipart_params    : options.multipart || {},
                url                 : options.url,
                chunk_size          : options.chunksize,
                filters : {
                    mime_types : [{
                        title : (options.types ? "Files" : (options.images ? "Image files" : "All files")),
                        extensions : (options.types ? options.types : (options.images ? "jpg,jpeg,gif,png" : "*"))
                    }],
                    max_file_size : options.size
                }
            });
        uploader.init();
        return uploader;
    };
    $.fn.plupload = function (settings) {
        return this.each(function () {
                var options     = $.extend(true, {}, $.plupload.defaults, ($(this).data('plupload') || {}), settings),
                    field       = $(this), i, j;
                if ($(this).closest('.plupload-container').length) {
                    $(this).insertBefore($(this).closest('.plupload-container'));
                    $(this).nextAll().not(':input').remove();
                    if (options.multiple) {
                        var resume = true;
                        if (settings.value && $.isArray(settings.value)) {
                            for (i = 0, j = settings.value.length; i < j; i++) {
                                if (!$.isPlainObject(settings.value[i]) && parseInt(settings.value[i], 10)) {
                                    resume = false;
                                    (function (i) {
                                        $.get(options.url + '/' + parseInt(settings.value[i], 10) + '?info=1').done(function (data) {
                                            data.html = data.name;
                                            settings.value[i] = data;
                                            var r = true, ids = [];
                                            for (var k = 0, l = settings.value.length; k < l; k++) {
                                                if (!$.isPlainObject(settings.value[k]) && parseInt(settings.value[k], 10)) {
                                                    r = false;
                                                } else {
                                                    ids.push(settings.value[k].id)
                                                }
                                            }
                                            if (r) {
                                                field.val(ids.join(','));
                                                field.plupload(settings);
                                            }
                                        });
                                    }(i));
                                }
                            }
                        }
                        if (!resume) {
                            return;
                        }
                    } else {
                        if (settings.value && !$.isPlainObject(settings.value) && parseInt(settings.value, 10)) {
                            $.get(options.url + '/' + parseInt(settings.value, 10) + '?info=1').done(function (data) {
                                data.html = data.name;
                                settings.value = data;
                                field.val(settings.value);
                                field.plupload(settings);
                            });
                            return;
                        }
                    }
                }
                var value       = $(this).val(),
                    container   = null,
                    upload      = null, i, j, temp,
                    update      = function () {
                        var tmp = [],
                            files = [];
                        container.find('.plupload-file').each(function () {
                            var id = $(this).data('id');//.toString();
                            if(id) {
                                id = id.toString();
                                tmp.push(id);
                                files.push({
                                    'id'    : id,
                                    'url'   : $(this).find('.plupload-title').attr('href'),
                                    'hash'  : $(this).data('hash'),
                                    'html'  : $(this).find('.plupload-title > span').text(),
                                    'thumb' : options.images ? $(this).css('backgroundImage').replace(/^url\(/i, '').replace(/\)$/, '') : ''
                                });
                            }
                        });
                        field.val(tmp.join(','));
                        if(options.changed) {
                            options.changed.call(this, options.multiple ? tmp : (tmp[0] || null), options.multiple ? files : (files[0] || null));
                        }
                        field.triggerHandler('changed.plupload', options.multiple ? files : (files[0] || null));
                    };

            // create container
            container = $(this)
                .wrap('<div class="plupload-container ' + (options.multiple ? 'plupload-multiple' : 'plupload-single') + ' ' + (options.images ? 'plupload-image-container' : 'plupload-document-container') + (options.disabled ? ' plupload-disabled' : '') + '"></div>').parent()
                .append('<div class="plupload-buttons"></div><div class="plupload-list"></div>')
                .children('.plupload-buttons')
                    .append('<a href="#" class="plupload-browse '+options.browse.clss+'"><i class="'+options.browse.icon+'"></i> '+options.browse.html+'</a>').end();
            if(options.multiple && options.download) {
                container.children('.plupload-buttons')
                    .append('<a href="' + options.download + '" class="plupload-zip '+options.zip.clss+'"><i class="'+options.zip.icon+'"></i> '+options.zip.html+'</a>');
            }
            if(!options.disabled && options.picker) {
                container.children('.plupload-buttons')
                    .append('<a href="#" class="plupload-picker '+options.pick.clss+'"><i class="'+options.pick.icon+'"></i> '+options.pick.html+'</a>');
            }
            if(options.value) {
                if(!$.isArray(options.value)) {
                    options.value = [options.value];
                }
                for(i = 0, j = options.value.length; i < j; i++) {
                    temp = $('' +
                        '<div data-id="'+(options.value[i].id || '')+'" data-hash="'+(options.value[i].hash || '')+'" class="plupload-complete plupload-file ' + (!options.disabled && options.multiple && options.dnd ? ' plupload-draggable ' : '') + (options.images ? 'plupload-image' : '') + ' '+(options.value[i].clss||'')+'">' +
                            (!options.disabled && options.multiple && options.dnd ? '<span class="plupload-drag"><i class="'+(options.file.drag)+'"></i></span>' : '' ) +
                            '<span class="plupload-remove '+(options.remove.clss)+'"><i class="'+(options.remove.icon)+'"></i>'+options.remove.html+'</span>' +
                            (options.settings ? '<span class="plupload-settings"><i class="'+(options.file.settings)+'"></i></span>' : '') +
                            (options.images && options.edit ? '<span class="plupload-edit"><i class="'+(options.file.edit)+'"></i></span>' : '') +
                            '<small class="plupload-details">'+(options.value[i].extra ? options.value[i].extra.join('&nbsp;&bull;&nbsp;') : '')+'</small>' +
                            '<a class="plupload-title" target="_blank" href="'+(options.value[i].url || '#')+'" draggable="false"><i class="'+(options.value[i].icon||options.file.done)+'"></i><span>'+(options.value[i].settings && options.value[i].settings.name ? options.value[i].settings.name : options.value[i].html) +'</span></a>' +
                            '<span class="plupload-progress"><span class="plupload-progress-inner"></span></span>' +
                        '</div>'
                    );
                    if (options.images) {
                        temp[0].style.backgroundImage = 'url(\''+(options.value[i].thumb||options.value[i].url||'')+'\')';
                    }
                    temp.find('.plupload-progress-inner')[0].style.width='100%';
                    container.children('.plupload-list').append(temp);
                    temp.data('settings', options.value[i].settings);
                }
            }
            if(!options.disabled && options.multiple && options.dnd) {
                var isdrg = 0,
                    initx = false,
                    inity = false,
                    ofstx = false,
                    ofsty = false,
                    holdr = false,
                    elmnt = false;
                container
                    .on('mousedown', '.plupload-title', function (e) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        return false;
                    })
                    .on('mousedown', '.plupload-drag, .plupload-image', function (e) {
                        elmnt = $(this);
                        if(elmnt.closest('.plupload-disabled').length) {
                            elmnt = false;
                            return;
                        }
                        try {
                            e.currentTarget.unselectable = "on";
                            e.currentTarget.onselectstart = function() { return false; };
                            if(e.currentTarget.style) { e.currentTarget.style.MozUserSelect = "none"; }
                        } catch(err) { }
                        holdr = false;
                        initx = e.pageX;
                        inity = e.pageY;
                        elmnt = $(this).closest('.plupload-file');
                        var o = elmnt.offset();
                        ofstx = e.pageX - o.left;
                        ofsty = e.pageY - o.top;
                        isdrg = 1;
                    });
                $('body')
                    .on('mousemove', function (e) {
                        switch(isdrg) {
                            case 0:
                                return;
                            case 1:
                                if(Math.abs(e.pageX - initx) > 5 || Math.abs(e.pageY - inity)) {
                                    holdr = $('<div id="plupload-holder" class="plupload-file ' + (container.hasClass('plupload-image-container') ? 'plupload-image' : '') + ' plupload-complete"> </div>');
                                    holdr[0].style.width = elmnt.outerWidth() + 'px';
                                    holdr[0].style.height = elmnt.outerHeight() + 'px';
                                    elmnt.after(holdr);
                                    //elmnt.appendTo('body').css({ 'position' : 'absolute', 'left' : e.pageX + 4, 'top' : e.pageY + 8, 'zIndex' : 4 });
                                    elmnt.addClass('plupload-dragged').parent().addClass('plupload-dragging').end().appendTo('body').css({ 'position' : 'absolute', 'left' : e.pageX - ofstx, 'top' : e.pageY - ofsty });
                                    if(!container.hasClass('plupload-image-container')) { elmnt.css('width', container.width()); }
                                    isdrg = 2;
                                }
                                break;
                            case 2:
                                elmnt.css({ 'left' : e.pageX - ofstx, 'top' : e.pageY - ofsty });
                                var targt = $(e.target).closest('.plupload-file'), i, j;
                                if(targt.length && targt[0] !== elmnt[0]) {
                                    i = targt.index();
                                    j = holdr.index();
                                    if(i != j) {
                                        targt[i>j?'after':'before'](holdr);
                                    }
                                }
                                break;
                        }
                    })
                    .on('mouseup', function () {
                        if(isdrg) {
                            if(isdrg == 2) {
                                holdr.replaceWith(elmnt);
                                //elmnt.css({ 'position':'relative', 'left':0, 'top':0 });
                                elmnt.parent().removeClass('plupload-dragging').end().removeClass('plupload-dragged').css({ 'position':'relative', 'left':0, 'top':0 });
                                if(!container.hasClass('plupload-image-container')) { elmnt.css('width', 'auto'); }
                                update();
                            }
                            isdrg = 0;
                            initx = false;
                            inity = false;
                            elmnt = false;
                            holdr = false;
                        }
                    });
            }

            if(!options.disabled) {
                upload = new plupload.Uploader({
                    runtimes            : options.runtimes,
                    browse_button       : container.find('.plupload-browse')[0],
                    headers             : {
                        'X-Requested-With' : 'XMLHttpRequest'
                    },
                    multipart           : true,
                    multipart_params    : options.multipart || {},
                    url                 : options.url,
                    chunk_size          : options.chunksize,
                    drop_element        : container.find('.plupload-list')[0],
                    filters : {
                        mime_types : [{
                            title : (options.types ? "Files" : (options.images ? "Image files" : "All files")),
                            extensions : (options.types ? options.types : (options.images ? "jpg,jpeg,gif,png" : "*"))
                        }],
                        max_file_size : options.size
                    }
                });

                if(options.settings) {
                    $('#' + options.settings).find(':input').prop('disabled', true).end().on('keydown', function (e) {
                        if (e.which === 13) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                        }
                    });
                    container.on('click', '.plupload-settings', function () {
                        var file = $(this).closest('.plupload-file'),
                            data = file.data('settings'),
                            form = $('#' + options.settings),
                            input, i;
                        form.find(':input').val('').end().find('.dropdown').dropdown('set exactly', []);
                        form.find('.dimmer').dimmer('hide');
                        if (data) {
                            for (i in data) {
                                if (data.hasOwnProperty(i)) {
                                    input = form.find('[name="' + i + '"], [name="' + i + '[]"]');
                                    if (data[i] !== null && input.length) {
                                        if (input.parent().hasClass('checkbox')) {
                                            input.val(parseInt(data[i], 10)).change();
                                            input.parent().children(':checkbox').prop('checked', !!parseInt(data[i], 10));
                                        } else if (input.parent().hasClass('tags')) {
                                            input.parent().dropdown('set exactly', data[i]);
                                        } else if (input.parent().hasClass('dropdown')) {
                                            input.parent().dropdown('set selected', data[i]);
                                        } else {
                                            input.val(data[i]);
                                        }
                                    }
                                }
                            }
                        }
                        form
                            .find('.close-button')
                                .off('click')
                                .click(function (e) {
                                    e.preventDefault();
                                    form.find(':input').prop('disabled', true).end().modal('hide');
                                }).end()
                            .find('.save-button')
                                .off('click')
                                .click(function (e) {
                                    e.preventDefault();
                                    var updated = {};
                                    form.find(':input[name]').prop('disabled', true).each(function () {
                                        updated[$(this).attr('name').replace('[]', '')] = $(this).val();
                                    });
                                    form.find('.dimmer').dimmer('show');
                                    $.ajax({
                                        type : 'POST',
                                        url : options.url,
                                        data : {
                                            id : file.data('id'),
                                            settings : JSON.stringify(updated)
                                        }
                                    })
                                        .done(function (data) {
                                            file.data('settings', updated);
                                            if (updated && updated.name) {
                                                file.find('.plupload-title > span').text(updated.name);
                                            }
                                            if (file.hasClass('plupload-image')) {
                                                file.css({'backgroundImage' : 'url('+ data.thumb + (data.thumb.indexOf('?') !== -1 ? '&_t=' : '?_t=') + Date.now() + ')'});
                                            }
                                            form.find('.dimmer').dimmer('hide');
                                        })
                                        .always(function () {
                                            form.find('.dimmer').dimmer('hide');
                                            form.modal('hide');
                                        });
                                }).end()
                            .find(':input').prop('disabled', false).end()
                            .modal('show');
                    });
                }
                if(options.images && options.edit) {
                    container.on('click', '.plupload-edit', function () {
                        var file = $(this).closest('.plupload-file'),
                            form = $('#' + options.edit),
                            sett = file.data('settings'),
                            format = file.children('a').attr('href').split('.').pop().toLowerCase().split('?')[0],
                            filename = file.children('a').text();
                        form.find('.dimmer').dimmer('hide');
                        form.find('img')
                            .attr('src', file.children('a').attr('href'))
                            .attr('id', options.edit + '_image');
                        var dkrm = new Darkroom('#' + options.edit + '_image', {
                            // Size options
                            minWidth: 100,
                            minHeight: 100,
                            maxWidth: 800,
                            maxHeight: 500,
                            backgroundColor: '#000',
                            plugins: {
                                save: false,
                                thumbnail : {
                                    x : sett && sett.thumbnail ? (sett.thumbnail.x || null) : null,
                                    y : sett && sett.thumbnail ? (sett.thumbnail.y || null) : null,
                                    w : sett && sett.thumbnail ? (sett.thumbnail.w || null) : null,
                                    h : sett && sett.thumbnail ? (sett.thumbnail.h || null) : null,
                                }
                            }
                        });
                        form.modal('setting', 'closable', false);
                        form
                            .find('.close-button')
                                .off('click')
                                .click(function (e) {
                                    e.preventDefault();
                                    form.modal('hide');
                                    dkrm.selfDestroy();
                                    form.find('img').attr('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=');
                                }).end()
                            .find('.save-button')
                                .off('click')
                                .click(function (e) {
                                    e.preventDefault();
                                    form.find('.dimmer').dimmer('show');
                                    if (dkrm.plugins['thumbnail'].hasFocus()) {
                                        dkrm.plugins['thumbnail'].releaseFocus()
                                    }
                                    if (dkrm.plugins['crop'].hasFocus()) {
                                        dkrm.plugins['crop'].releaseFocus()
                                    }
                                    var ow = dkrm.canvas.getWidth();
                                    var oh = dkrm.canvas.getHeight();
                                    var tr = dkrm.transformations.slice();
                                    dkrm.image.scaleX = 1;
                                    dkrm.image.scaleY = 1;
                                    dkrm.transformations = [];
                                    dkrm.canvas
                                        .setDimensions({ width: dkrm.image.width, height: dkrm.image.height }, { backstoreOnly: true })
                                        .renderAll();
                                    dkrm.canvas.renderAll().getElement().toBlob(function (blob) {
                                        var thumb = {
                                            x: dkrm.plugins['thumbnail'].options.x,
                                            y: dkrm.plugins['thumbnail'].options.y,
                                            w: dkrm.plugins['thumbnail'].options.w,
                                            h: dkrm.plugins['thumbnail'].options.h
                                        };
                                        var formData = new FormData();
                                        formData.append("id", file.data('id'));
                                        formData.append("thumbnail", JSON.stringify(thumb));
                                        formData.append("crop", JSON.stringify(tr));
                                        //formData.append("image", blob, filename);
                                        var request = new XMLHttpRequest();
                                        request.open("POST", options.url, true);
                                        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                                        request.onload = function () {
                                            if (request.status === 200) {
                                                if (!sett) {
                                                    sett = {};
                                                }
                                                sett.thumbnail = thumb;
                                                var data = JSON.parse(request.responseText);
                                                file.data('settings', sett);
                                                file.data('id', data.id);
                                                file.data('hash', data.hash);
                                                var img = data.thumb || data.url || '';
                                                file
                                                    .find('a').attr('href', data.url).end()
                                                    .css({'backgroundImage' : 'url('+ img + (img.indexOf('?') !== -1 ? '&_t=' : '?_t=') + Date.now() + ')'});
                                                update();
                                            }
                                            form.find('.dimmer').dimmer('hide');
                                            form.modal('hide');
                                            dkrm.selfDestroy();
                                            setTimeout(function () {
                                                form.find('img').attr('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=');
                                            }, 50);
                                        };
                                        request.send(formData);
                                    }, format === 'png' ? 'image/png' : 'image/jpeg', 1);
                                }).end()
                            .modal('show');
                    });
                }
                container
                    .on('click', '.plupload-picker', function (e) {
                        e.preventDefault();
                        $('#picker-modal')
                            .html('<iframe class="module-field-iframe" src="" width="100%" height="80vh"></iframe>')
                            .find('iframe')
                                .off('load')
                                .on('load', function () {
                                    var iframe = this.contentWindow;
                                    if (iframe.selectedPromise) {
                                        iframe.selectedPromise.then(function (vv) {
                                            var url = vv.html.find('[data-url]').data('url');
                                            $.get(url + '?info=1')
                                                .done(function (file) {
                                                    var cnt = container.children('.plupload-list');
                                                    if (!options.multiple) {
                                                        cnt.find('.plupload-remove').click();
                                                    }
                                                    cnt.append(
                                                        '<div data-id="' + file.id + '" data-hash="' + file.hash + '" class="plupload-complete plupload-file ' + (!options.disabled && options.multiple && options.dnd ? ' plupload-draggable ' : '') + (options.images ? 'plupload-image' : '') + '">' +
                                                            (!options.disabled && options.multiple && options.dnd ? '<span class="plupload-drag"><i class="'+(options.file.drag)+'"></i></span>' : '' ) +
                                                            '<span class="plupload-remove '+(options.remove.clss)+'"><i class="'+(options.remove.icon)+'"></i>'+options.remove.html+'</span>' +
                                                            (options.settings ? '<span class="plupload-settings"><i class="'+(options.file.settings)+'"></i></span>' : '') +
                                                            (options.images && options.edit ? '<span class="plupload-edit"><i class="'+(options.file.edit)+'"></i></span>' : '') +
                                                            '<a class="plupload-title" href="' + file.url + '" target="_blank" draggable="false"><i class="'+(options.file.done)+'"></i><span>'+file.name+'</span></a>' +
                                                            '<span class="plupload-progress"><span class="plupload-progress-inner"></span></span>' +
                                                        '</div>'
                                                    );
                                                    cnt.find('[data-id="' + file.id + '"]').eq(0).find('.plupload-progress-inner').css({ 'width':'100%' });
                                                    if (options.images) {
                                                        cnt.find('[data-id="' + file.id + '"]')[0].style.backgroundImage = 'url(\''+(file.thumb||file.url||'')+'\')';
                                                    }
                                                    upload.refresh();
                                                    update();
                                                })
                                                .always(function () {
                                                    $('#picker-modal').modal('hide');
                                                })
                                        });
                                    } else {
                                        $('#picker-modal').modal('hide');
                                    }
                                })
                                .attr('src', options.picker)
                                .end()
                            .modal('show');
                    });
                container
                    .on('click', '.plupload-tags', function (e) {
                        e.preventDefault();
                        $('#picker-modal')
                            .html('<iframe class="module-field-iframe" src="" width="100%" height="80vh"></iframe>')
                            .find('iframe')
                                .off('load')
                                .on('load', function () {
                                    var iframe = this.contentWindow;
                                    if (iframe.selectedPromise) {
                                        iframe.selectedPromise.then(function () {
                                            $('#picker-modal').modal('hide');
                                        })
                                    }
                                })
                                .attr('src', options.picker.split('?')[0] + '/update/' + $(e.target).closest('.plupload-file').data('id'))
                            .end()
                            .modal('show');
                    });
                container
                    .on('click', '.plupload-remove', function (e) {
                        e.preventDefault();
                        var pf = $(e.target).closest('.plupload-file'),
                            id = pf[0].dataset.id;
                        pf.remove();
                        // може да не пробва ако няма клас
                        try {
                            upload.stop();
                            upload.removeFile(upload.getFile(id));
                            upload.start();
                        } catch(ignore) { }
                        upload.refresh();
                        update();
                        return false;
                    })
                    .on('dragover', function () {
                        $(this).addClass('plupload-hover');
                    })
                    .on('dragexit drop', function () {
                        $(this).removeClass('plupload-hover');
                    })
                    .closest('form')
                        .on('submit', function (e) {
                            if($(this).find('.plupload-uploading:eq(0), .plupload-wait:eq(0)').length) {
                                alert('Файловете още се прикачат. \nМоля, изчакайте или спрете качването.');
                                e.preventDefault();
                                return false;
                            }
                        })
                        .on('reset', function (e) {
                            $(this).find('.plupload-file').remove();
                            update();
                        });

                    upload.bind('PostInit', function(up, params) {
                        setTimeout(function () { up.refresh(); }, 100);
                    });
                    upload.bind('FilesAdded', function(up, files) {
                        $.each(files, function (i, v) {
                            var cnt = container.children('.plupload-list'),
                                cur = cnt.children('.plupload-file').length;
                            if(cur && !options.multiple) {
                                var pf = cnt.find('.plupload-file'),
                                    id = pf[0].dataset.id;
                                pf.remove();
                                // може да не пробва ако няма клас
                                try {
                                    if(id) {
                                        up.removeFile(up.getFile(id));
                                    }
                                } catch(e) { }
                                cur = 0;
                                update();
                            }
                            if (options.multiple && options.count && cur >= options.count) {
                                return true;
                            }
                            if(container.hasClass('plupload-image-container') && URL) {
                                try {
                                    (function (id, data) {
                                        setTimeout(function () {
                                            var img = new Image();
                                            img.onload = function () {
                                                var cnv1 = document.createElement('CANVAS'),
                                                    cnt1 = cnv1.getContext('2d'),
                                                    cnv2 = document.createElement('CANVAS'),
                                                    cnt2 = cnv2.getContext('2d');
                                                cnv1.setAttribute('width', img.width);
                                                cnv1.setAttribute('height', img.height);
                                                cnt1.drawImage(img, 0, 0);
                                                cnv2.setAttribute('width', '120');
                                                cnv2.setAttribute('height', '120');
                                                cnt2.drawImage(cnv1, Math.max(0, (img.width - img.height) / 2), Math.max(0, (img.height - img.width) / 2), Math.min(img.width, img.height), Math.min(img.width, img.height), 0, 0, 120, 120);
                                                container
                                                    .find('[data-id="' + id + '"]').filter('.plupload-wait, .plupload-uploading')
                                                    .css({ 'backgroundImage' : 'url("' + cnv2.toDataURL("image/png") + '")' });
                                            };
                                            img.src = URL.createObjectURL(data);
                                        }, i * 20);
                                    }(v.id, v.getNative()));
                                } catch(ignore) { }
                            }
                            cnt.append(
                                '<div data-id="' + files[i].id + '" class="plupload-wait plupload-file ' + (!options.disabled && options.multiple && options.dnd ? ' plupload-draggable ' : '') + (options.images ? 'plupload-image' : '') + '">' +
                                    (!options.disabled && options.multiple && options.dnd ? '<span class="plupload-drag"><i class="'+(options.file.drag)+'"></i></span>' : '' ) +
                                    '<span class="plupload-remove '+(options.remove.clss)+'"><i class="'+(options.remove.icon)+'"></i>'+options.remove.html+'</span>' +
                                    (options.settings ? '<span class="plupload-settings"><i class="'+(options.file.settings)+'"></i></span>' : '') +
                                    (options.images && options.edit ? '<span class="plupload-edit"><i class="'+(options.file.edit)+'"></i></span>' : '') +
                                    (options.picker ? '<span class="plupload-tags"><i class="'+(options.file.tags)+'"></i></span>' : '') +
                                    '<a class="plupload-title" href="#" target="_blank" draggable="false"><i class="'+(options.file.wait)+'"></i><span>'+files[i].name+'</span></a>' +
                                    '<span class="plupload-progress"><span class="plupload-progress-inner"></span></span>' +
                                '</div>'
                            );
                        });
                        setTimeout(function () { $.each(up.files, function (i,v) { if(v && v.id && !container.find('[data-id="' + v.id + '"]')) { try { up.removeFile(v); update(); } catch(e) { } } }); up.refresh(); up.start(); },100);
                    });
                    upload.bind('BeforeUpload', function(up, file) {
                        //params = plupload.settings.multipart_params;
                        //params.prefix = params.prefix.split('_')[0] + '_' + file.id;
                        container.find('[data-id="' + file.id + '"]').removeClass("plupload-wait").addClass('plupload-uploading');
                    });
                    upload.bind('UploadProgress', function(up, file) {
                        container.find('[data-id="' + file.id + '"]').find('.plupload-progress-inner').css('width', file.percent + '%');
                    });
                    upload.bind('FileUploaded', function(a,b,c) {
                        // IE9!!!
                        c = JSON.parse(c.response.replace(/<\/?pre\>/ig,'').replace(/\r/g,''));
                        if(parseInt(c.id,10) || options.multipart.temp) {
                            container.find('[data-id="' + b.id + '"]')
                                .removeClass("plupload-uploading plupload-wait")
                                .addClass('plupload-complete')
                                .data('hash', c.hash)
                                .data('id', c.id)
                                .find('.plupload-progress-inner').css({ 'width':'100%' }).end()
                                .find('.plupload-title').attr('href', c.url).end()
                                .find('.plupload-title > i').attr('class', options.file.done);
                            if(options.images && (c.thumb || c.url)) {
                                container.find('[data-id="' + b.id + '"]').css({'backgroundImage' : 'url('+ (c.thumb || c.url) + ')'});
                            }
                            update();
                        }
                        else {
                            container.find('[data-id="' + b.id + '"]').remove();
                            alert('Грешка при качване:' + "\n\n" + 'Не можахме да съхраним файла.');
                        }
                        a.refresh();
                    });
                    upload.bind('Error', function(up, e) {
                        if(e.file && e.file.id) {
                            container.find('[data-id="' + e.file.id + '"]').remove();
                        }
                        alert('Грешка при качване:' + "\n\n" + e.message);
                        up.refresh();
                    });

                upload.init();
            }
        });
    };

    $(function () {
        $('body')
            .bind('dragover', function () {
                $('.plupload-container').not('.plupload-disabled').addClass('plupload-target');
            })
            .bind('dragexit drop', function () {
                $('.plupload-container').not('.plupload-disabled').removeClass('plupload-target');
            });
    });

}));
