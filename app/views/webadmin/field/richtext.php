<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\http\Request $req
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 * @var string $cspNonce
 * @var \vakata\http\Uri $url
 */
?>
<?php if ($field->getOption('textOnly')) : ?>
    <?php if (strlen($field->getOption('label', ''))) : ?>
        <label>
            <?php if ($field->getOption('tooltip')) : ?>
                <span 
                    data-tooltip="<?= $this->e($intl($field->getOption('tooltip'))) ?>"
                    data-inverted="">
                    <i class="question circle icon"></i>
                </span>
            <?php endif ?>
            <?= $this->e($intl($field->getOption('label'))) ?>
        </label>
    <?php endif ?>
    <div><?= $field->getValue('') ?></div>
<?php else : ?>
    <?php
    if (!$field->hasAttr('id')) {
        $field->setAttr('id', 'richtext_' . md5($field->getName('') . microtime() . rand(0, 100)));
    }
    $id = $field->getAttr('id');
    $field->addClass('richtext richtext-waiting');
    $disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
    $field->setAttr('data-tinymce', $field->getOptions());
    echo $this->insert('webadmin::field/textarea', [ 'field' => $field ]);
    ?>
    <div id="modal_<?= $this->e($id) ?>" class="ui fullscreen modal"></div>
    <script nonce="<?= $this->e($cspNonce) ?>">
    setTimeout(function () {
        var create = function () {
            var rw = $('.richtext-waiting');
            rw.each(function () {
                var obj = $(this);
                if (obj.is(':visible')) {
                    obj.removeClass('richtext-waiting');
                    (function (id, config) {
                        tinymce.baseURL = "<?= $url('assets/static/tinymce') ?>";
                        tinymce.init($.extend({
                            language : "<?= $this->e($intl('_locale.code.long')) ?>",
                            language_url : 
                                "<?= $url('assets/tinymce_langs/' . ($req->getAttribute('locale') ?? 'en') . '.js') ?>",
                            selector : '#' + id,
                            setup: function (editor) {
                                editor.on('change', function () {
                                    editor.save();
                                });
                            },
                            browser_spellcheck: true,
                            contextmenu: false,
                            paste_data_images : true,
                            promotion: false,
                            plugins: 
                                "advlist autolink link image lists charmap anchor pagebreak "+
                                "searchreplace visualchars code insertdatetime media nonbreaking "+
                                "save table ",
                            images_upload_handler: function (blobInfo, progress) {
                                return new Promise(function (resolve, reject) {
                                    var xhr = new XMLHttpRequest(),
                                        formData = new FormData();
                                    xhr.open('POST', "<?= $url($config('UPLOAD_URL')) ?>");
                                    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
                                    xhr.onload = function() {
                                        var json;
                                        if (xhr.status != 200) {
                                            return reject('HTTP Error: ' + xhr.status);
                                        }
                                        json = JSON.parse(xhr.responseText);

                                        if (!json || typeof json.url != 'string') {
                                            reject('Invalid JSON: ' + xhr.responseText);
                                        } else {
                                            resolve(json.url);
                                        }
                                    }
                                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                                    xhr.send(formData);
                                });
                            },
                            menubar: <?= ($disabled) || (isset($readonly) && $readonly) ?
                                'false' : 'true' ?>,
                            statusbar: false,
                            menu : {
                                file   : {title : 'File'  , items : 'newdocument'},
                                edit   : {title : 'Edit'  , items : 'undo redo | cut copy paste pastetext | selectall'},
                                insert : {title : 'Insert', items : 'link media | template hr'},
                                view   : {title : 'View'  , items : 'visualaid'},
                                format : {
                                    title : 'Format',
                                    items : 'bold italic underline strikethrough superscript subscript | formats | ' +
                                        'removeformat'
                                },
                                table  : {title : 'Table' ,
                                    items : 'inserttable tableprops deletetable | cell row column'},
                                tools  : {title : 'Tools' ,
                                    items : 'spellchecker code'},
                            },
                            toolbar: <?= ($disabled) || (isset($readonly) && $readonly) ?
                                'true' : 'false' ?> ?
                                [] :
                                [
                                    "undo redo | insert bold italic underline | "+
                                    "alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | "+
                                    "link image | forecolor backcolor | searchreplace | code"
                                ],
                            save_enablewhendirty: false,
                            image_advtab : true,
                            document_base_url: "<?= $url('') ?>",
                            relative_urls: false,
                            file_picker_callback: function(callback, value, meta) {
                                $('#picker-modal')
                                    .html(
                                        '<iframe class="module-field-iframe" src="" width="100%" height="80vh">'+
                                        '</iframe>'
                                    )
                                    .find('iframe')
                                        .off('load')
                                        .on('load', function () {
                                            var iframe = this.contentWindow;
                                            if (iframe.selectedPromise) {
                                                iframe.selectedPromise.then(function (vv) {
                                                    var url = vv.html.find('[data-url]').data('url');
                                                    $.get(
                                                        url + '?info=1'
                                                    )
                                                        .done(function (file) {
                                                            callback(file.url)
                                                        })
                                                        .always(function () {
                                                            $('#picker-modal').modal('hide');
                                                        })
                                                });
                                            } else {
                                                $('#picker-modal').modal('hide');
                                            }
                                        })
                                        .attr('src', "<?= $url('uploads') . '#add' ?>")
                                        .end()
                                    .modal('show');
                            },
                            height: '400px',
                            readonly : <?= $disabled ? 'true' : 'false' ?>
                        }, config));
                    }(obj[0].id, obj.data('tinymce') || {}));
                }
            });
            if (window._vakata_tinymce_timeout) {
                clearTimeout(window._vakata_tinymce_timeout);
            }
            if ($('.richtext-waiting').length) {
                setTimeout(create, 2000);
            }
        };
        if (window._vakata_tinymce_timeout) {
            clearTimeout(window._vakata_tinymce_timeout);
        }
        window._vakata_tinymce_timeout = setTimeout(create, 100);
    }, 100);
    </script>
<?php endif ?>
