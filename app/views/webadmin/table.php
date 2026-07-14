<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Table $table
 * @var string $cspNonce
 * @var \vakata\intl\Intl $intl
 * @var \vakata\http\Uri $url
 */
?>
<?php
$id = 'table_' . md5(microtime() . rand(0, 100));
$tablename = $table->getAttr('x-data-name', 'table');
$count = $table->getAttr('x-data-count', count($table->getRows()));
$params = array_merge([ 'p' => 1, 'l' => 25 ], $table->getAttr('x-data-params', []));
$query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
$filtered = $params;
unset($filtered['p'], $filtered['l'], $filtered['d'], $filtered['o']);
$filtered = count($filtered) > 0;
$paging = $table->getAttr('x-data-paging', $count > $params['l']);
$filters = $table->getAttr('x-data-filters', []);
$search = $table->getAttr('x-data-search', true);
$hidden = [];
foreach ($table->getColumns() as $column) {
    if ($column->isHidden()) {
        $hidden[] = $column->getName();
    }
}
$dnd = $table->hasAttr('data-dnd') && !$table->hasClass('table-filtered');
?>

<div class="ui segment">
    <div class="ui fluid grid">
        <?php if ($search || count($filters) || count($table->getOperations())) : ?>
        <div class="ui stackable two column row crud-header">
            <div class="ui column">
                <?php if (count($table->getOperations())) : ?>
                <div class="row-operations ui clearing basic fitted segment">
                    <?php foreach ($table->getOperations() as $button) : ?>
                        <a
                            href="<?= $this->e($url($button->getAttr('href'))) ?>"
                            class="ui <?= $this->e($button->getClass()) ?>"
                        >
                            <?php if ($button->getIcon()) : ?>
                                <i class="<?= $this->e($button->getIcon()) ?> icon"></i>
                            <?php endif ?>
                            <?php if ($button->getLabel()) : ?>
                                <?= $this->e($intl($button->getLabel())) ?>
                            <?php endif ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif ?>
            </div>
            <div class="ui right aligned column">
                <div class="filters-column"></div>
                <?php if ($search) : ?>
                <form class="filters-form">
                    <?php foreach ($params as $k => $v) : ?>
                        <?php
                        if (in_array($k, ['q','p'])) {
                            continue;
                        }
                        ?>
                        <?php if (is_array($v)) : ?>
                            <?php foreach ($v as $kk => $vv) : ?>
                                <?php
                                if (is_array($vv)) {
                                    continue;
                                }
                                ?> 
                                <input type="hidden" name="<?= $this->e($k . '[' . $kk . ']') ?>"
                                    value="<?= $this->e($vv) ?>" />
                            <?php endforeach ?>
                        <?php else : ?>
                            <input type="hidden" name="<?= $this->e((string)$k) ?>" value="<?= $this->e($v) ?>" />
                        <?php endif ?>
                    <?php endforeach; ?>
                    <input type="hidden" name="p" value="1" />
                    <div class="ui action input">
                        <input placeholder="<?= $this->e($intl('common.search')) ?> ..." type="text" name="q"
                            value="<?= $this->e($params['q'] ?? '') ?>" />
                        <?php if (isset($params['q']) && strlen($params['q'])) : ?>
                        <a href="?<?= $this->e(http_build_query(array_merge($params, ['q' => '', 'p' => 1]))) ?>"
                            class="ui icon button">
                            <i class="remove icon"></i>
                        </a>
                        <?php endif; ?>
                        <button class="ui blue icon button"><i class="transparent search icon"></i></button>
                    </div>
                </form>
                <?php endif ?>
            </div>
        </div>
        <?php endif ?>
        <div class="ui one column row">
            <div class="ui column">

<?php if ($filtered) : ?>
    <div class="ui attached small icon message">
        <i class="filter icon"></i>
        <div class="content">
            <a href="?" class="ui right floated icon orange labeled button">
                <i class="remove icon"></i>
                <?= $this->e($intl('crud.clearfilters')) ?>
            </a>
            <div class="header"><?= $this->e($intl('crud.dataisfiltered')) ?></div>
            <p><?= $this->e($intl('crud.dataisfilteredlong')) ?></p>
        </div>
    </div>
<?php endif ?>

<?php $activeFilters = []; ?>
<?php foreach ($table->getColumns() as $name => $column) : ?>
    <?php if ($column->hasFilter()) : ?>
        <div id="<?= $id . '_' . $this->e(str_replace('.', '__', (string)$name)) ?>"
            class="ui flowing popup bottom left transition hidden filter-popup">
            <?php
            /* @phpstan-ignore-next-line */
            $form = $column->getFilter()->populate($params);
            $fields = [];
            foreach (\vakata\collection\Collection::from($form->getFields()) as $v) {
                $fields[] = explode('[', $v->getName(''))[0];
            }
            $active = count(array_intersect(array_keys($params), $fields)) > 0;
            if ($active) {
                $activeFilters[] = $name;
            }
            if ($active && isset($params[$name])) {
                $temp = [];
                $temp[$name] = $params[$name];
                $temp = explode('=', urldecode(http_build_query($temp)))[0];
                foreach ($form->getFields() as $field) {
                    if ($field->hasClass('filter-modifier')) {
                        $field->setValue($temp);
                    }
                    if ($field->hasClass('filter-modified')) {
                        $field->setAttr('name', $temp);
                    }
                }
                $form->populate($params);
            }
            ?>
            <form class="ui form" method="get">
                <?php foreach ($params as $k => $v) : ?>
                    <?php
                    if (in_array($k, $fields)) {
                        continue;
                    }
                    $not = false;
                    ?>
                    <?php if (is_array($v)) : ?>
                        <?php
                        if (array_keys($v)[0] === 'not') {
                            $not = true;
                            $v = $v['not'];
                        }
                        ?>
                    <?php endif ?>
                    <?php if (is_array($v)) : ?>
                        <?php foreach ($v as $kk => $vv) : ?>
                            <input type="hidden" 
                                name="<?= $k ?><?= $not ? '[not]' : '' ?>[<?= !is_numeric($kk) ? $kk : '' ?>]"
                                value="<?= $this->e(is_string($vv) ? $vv : '') ?>" />
                        <?php endforeach ?>
                    <?php else : ?>
                        <input type="hidden" name="<?= $k ?><?= $not ? '[not]' : '' ?>"
                            value="<?= $this->e($k === 'p' ? 1 : $v) ?>" />
                    <?php endif ?>
                <?php endforeach ?>

                <?= $this->insert('webadmin::form', [ 'form' => $form ]) ?>

                <div class="ui center aligned green secondary segment">
                    <button class="ui tiny green submit button"><?= $this->e($intl('common.filter.filter')) ?></button>
                    <?php if ($active) : ?>
                        <?php
                        $temp = $params;
                        foreach ($fields as $name) {
                            unset($temp[$name]);
                        }
                        $temp['p'] = 1;
                        ?>
                        <a class="ui tiny basic button" href="?<?= http_build_query($temp) ?>">
                            <?= $this->e($intl('common.filter.clear')) ?>
                        </a>
                    <?php endif ?>
                </div>
            </form>
        </div>
    <?php endif ?>
<?php endforeach ?>
<div id="<?= $id . '__column_chooser' ?>"
    class="ui column-chooser flowing popup bottom left transition hidden filter-popup">
    <?php
    $form = new \webadmin\components\html\Form();
    $col = [];
    foreach ($table->getColumns() as $column) {
        $form->addField(
            new \webadmin\components\html\Field(
                'checkbox',
                [
                    'name'  => $this->e('column_chooser_' . $column->getName()),
                    'value' => '1'
                ],
                [ 'label' => $tablename . '.columns.' . $column->getName(), 'nobr' => true ]
            )
        );
        $col[] = 'column_chooser_' . $column->getName();
    }
    $cnt = floor(count($col) / 6) + 1;
    if (count($col) % $cnt !== 0) {
        $col = array_pad($col, (int)(count($col) + ($cnt - count($col) % $cnt)), '');
    }
    /** @phpstan-ignore-next-line */
    $col = array_chunk($col, (int)$cnt);
    $form->setLayout($col);
    echo $this->insert('webadmin::form', [
        'form' => $form
    ]);
    ?>
</div>

<table 
    class="ui
        <?= $this->e($table->getAttr('class')) ?> main-table <?= !$count ? ' empty-table ' : '' ?> 
        <?= $filtered ? 'attached table-filtered' : '' ?>
        single line table"
    id="<?= $id ?>"
    <?php foreach ($table->getAttrs() as $k => $v) : ?>
        <?php
        if (in_array($k, [ 'id', 'class' ])) {
            continue;
        }
        if (strpos((string)$k, 'x-data-') === 0) {
            continue;
        }
        if (is_array($v) || is_object($v)) {
            echo $this->e((string)$k) . '=\'';
            echo json_encode(
                $v,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT
            ) . '\' ';
        } else {
            echo $this->e((string)$k) . '="' . $this->e($v) . '" ';
        }
        ?>
    <?php endforeach ?>
    >
    <thead>
        <tr>
            <?php foreach ($table->getColumns() as $name => $column) : ?>
                <th data-column="<?= $this->e((string)$name) ?>"
                    class="<?= $column->hasFilter() ? 'has-filter' : '' ?>">
                    <?php if ($column->hasFilter()) : ?>
                        <button
                            data-popup="<?= $id . '_' . $this->e(str_replace('.', '__', (string)$name)) ?>"
                            class="filter ui right floated <?= in_array($name, $activeFilters) ? 'orange' : '' ?>
                                basic mini compact icon button"
                        >
                            <i class="filter icon"></i>
                        </button>
                    <?php endif ?>
                    <?php if ($column->isSortable()) : ?>
                        <a href="?<?= $this->e(http_build_query(array_merge(
                            $params,
                            [
                                'o' => $name,
                                'd' => (isset($params['o']) &&
                                    $params['o'] === $name &&
                                    isset($params['d']) &&
                                    (int)$params['d'] === 0 ?
                                        1 : 0
                                )
                            ]
                        ))) ?>">
                            <?= $this->e($intl($tablename . '.columns.' . $name)) ?>
                            <?php if (isset($params['o']) && $params['o'] === $name) : ?>
                                <i class="caret <?= isset($params['d']) && (int)$params['d'] === 1 ? 'down' : 'up' ?> 
                                    icon"></i>
                            <?php endif; ?>
                        </a>
                    <?php else : ?>
                        <?= $this->e($intl($tablename . '.columns.' . $name)) ?>
                    <?php endif ?>
                </th>
            <?php endforeach; ?>
            <th class="single line operations">
                <button data-popup="<?= $id . '__column_chooser' ?>" class="filter ui basic mini compact icon button">
                    <i class="settings icon"></i>
                </button>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$count) : ?>
            <tr>
                <td colspan="<?php echo (count($table->getColumns()) + 1); ?>" class="center aligned">
                    <div class="ui message">
                        <?= $this->e($intl('common.table.norecords')) ?>
                        <?php if ($filtered) : ?>
                            <?= $this->e($intl('common.table.matching')) ?><br /><br />
                            <a href="?" class="ui tiny labeled icon teal button">
                                <i class="remove icon"></i>
                                <?= $this->e($intl('common.table.clearfilters')) ?>
                            </a>
                        <?php endif ?>
                    </div>
                </td>
            </tr>
        <?php else : ?>
            <?php foreach ($table->getRows() as $row) : ?>
            <tr class="<?= $this->e($row->getClass()) ?>" data-id='<?= $this->e($row->getAttr('id', '')) ?>'>
                <?php foreach ($table->getColumns() as $column) : ?>
                    <td class="<?= $this->e($column->getClass()) ?>" data-column="<?= $this->e($column->getName()) ?>">
                    <?php
                    if ($column->hasQuickFilter()) {
                        $temp = explode('.', $column->getQuickFilter() ?? '');
                        $filt = $row->getData();
                        foreach ($temp as $part) {
                            if (
                                $filt === null ||
                                !is_object($filt) ||
                                (!property_exists($filt, $part) && !method_exists($filt, '__get'))
                            ) {
                                $filt = '';
                                break;
                            }
                            $filt = $filt->{$part};
                        }
                        echo '<i data-column="' . $this->e($column->getQuickFilter() ?? '') . '" ' .
                            'data-value="' .
                                htmlspecialchars((string)$filt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                            '" ' .
                            'class="quick-filter ui filter icon"></i>';
                    }
                    $temp = explode('.', $column->getName());
                    $value = $row->getData();
                    foreach ($temp as $part) {
                        if (
                            $value === null ||
                            !is_object($value) ||
                            (!property_exists($value, $part) && !method_exists($value, '__get'))
                        ) {
                            $value = '';
                            break;
                        }
                        $value = $value->{$part};
                    }
                    if ($column->hasMap() && $column->getMap()) {
                        $value = call_user_func($column->getMap(), $value, $row->getData());
                    }
                    echo $value instanceof \webadmin\components\html\HTML ? (string)$value : $this->e((string)$value);
                    ?>
                    </td>
                <?php endforeach; ?>
                <td class="operations">
                    <?php foreach ($row->getOperations() as $button) : ?>
                        <a
                            href="<?= $this->e($url($button->getAttr('href'))) ?>"
                            class="ui <?= $this->e($button->getClass()) ?>"
                            title="<?= $this->e($intl($button->getLabel())) ?>"
                        >
                            <i class="<?= $this->e($button->getIcon() ?? '') ?> icon"></i>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($dnd) : ?>
                    <i class="ui vertical ellipsis icon json-reorder-row"></i>
                    <?php endif ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($paging && $count) : ?>
    <div class="ui attached center aligned segment">
        <?php if ($count > $params['l']) : ?>
            <div class="ui center small pagination menu">
                <a 
                    <?php if ($params['p'] > 1) : ?>
                    href="?<?php
                        echo $this->e(http_build_query(array_merge($params, ['p' => $params['p'] - 1])))
                    ?>"
                    <?php endif ?>
                    class="icon <?= $this->e(($params['p'] <= 1 ? 'disabled' : '')) ?> item"
                ><i class="left chevron icon"></i></a>
                <?php
                    $t_rcrd = (int)$count;
                    $p_page = (int)$params['l'];
                    $s_page = 1;
                    $e_page = (int)ceil($count / $params['l']);
                    $c_page = (int)$params['p'];
                    $s_rcrd = (max($c_page - 1, 0) * $p_page) + 1;
                    $e_rcrd = $s_rcrd + count($table->getRows()) - 1;
                for ($i = 1; $i <= $e_page; $i++) {
                    if ($e_page > 16 && $i > 3 && $i < $e_page - 3) {
                        if ($c_page > 7 && $c_page < $e_page - 6) {
                            if ($i < $c_page - 3) {
                                echo '<span class="disabled item">&hellip;</span>';
                                $i = $c_page - 3;
                                continue;
                            }
                            if ($i > $c_page + 2) {
                                echo '<span class="disabled item">&hellip;</span>';
                                $i = $e_page - 3;
                                continue;
                            }
                        } else {
                            if ($i == 9) {
                                echo '<span class="disabled item">&hellip;</span>';
                                $i = max($e_page - 8, 10);
                                continue;
                            }
                        }
                    }
                    echo '<a href="?' . $this->e(
                        http_build_query(array_merge($params, ['p' => $i ]))
                    ) . '" ';
                    echo 'class="paging-number ' . $this->e($params['p'] == $i ? 'active' : '') . ' item">';
                    echo $i . '</a>';
                }
                ?>

                <a 
                    <?php if ($c_page < $e_page) : ?>
                    href="?<?= $this->e(http_build_query(array_merge($params, ['p' => $c_page + 1 ]))) ?>"
                    <?php endif ?>
                    class="icon <?= $this->e($c_page >= $e_page ? 'disabled' : '') ?> item"
                ><i class="right chevron icon"></i></a>
            </div>
        <?php endif ?>
        <small class="paging-stats">
            <?=
                $this->e(
                    $intl(
                        'common.table.records',
                        [
                            'beg' => ($params['p'] - 1) * $params['l'] + 1,
                            'end' => min($count, ($params['p'] - 1) * $params['l'] + $params['l']),
                            'total' => $count
                        ]
                    )
                )
            ?>
        </small>
    </div>
<?php endif; ?>

            </div>
        </div>
    </div>
</div>

<div id="save-filter-modal" class="ui small modal">
    <div class="ui form">
        <div class="one field">
            <label><?= $this->e($intl('crud.save_filter_name')) ?></label>
            <div class="ui required input">
                <input type="text" required />
            </div>
        </div>
        <div class="ui section divider"></div>
        <div class="ui center aligned teal secondary segment">
            <button class="ui teal icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
            <div class="ui basic cancel button">
                <?= $this->e($intl('common.cancel')) ?>
            </div>
        </div>
    </div>
</div>

<style nonce="<?= $this->e($cspNonce) ?>">
.paging-stats { display:block; color:gray; padding-top:1rem; }
#save-filter-modal .form { padding:20px; }
#<?= $id . '__column_chooser' ?>.filter-popup { text-align:left !important; }
#<?= $id . '__column_chooser' ?> i.ellipsis { cursor:move; float:right; }
.column-chooser { padding-bottom:1.8rem !important; }
.column-chooser .row { padding-bottom:0 !important; }
.filter-popup > form { min-width:240px !important; }
#<?= $id ?> td:not(:last-child) { cursor:pointer; }
.jquery-dtpckr-popup { z-index:999999 !important; }
i.quick-filter { float:right; color:silver; opacity:0; margin-top:3px; }
td:hover > .quick-filter { opacity:1; }
.quick-filter:hover { color:black; }

#<?= $id ?> td > .thumb { display:none; }
#<?= $id ?>.thumbs-table td > .thumb { display:block; }
.thumbs-table .thumb { height:200px; vertical-align: middle; line-height: 200px; }
.thumbs-table .thumb img { max-height:180px; max-width:280px; vertical-align: middle; }

.thumbs-table { display:block !important; }
.thumbs-table thead { display: block !important; }
.thumbs-table thead tr { display:flex !important; flex-direction: row; }
.thumbs-table thead th { display:inline-block !important; flex-grow:1; }
.thumbs-table tfoot,
.thumbs-table tfoot tr,
.thumbs-table tfoot th { width:100%; display:block !important; }
.thumbs-table tbody { white-space:normal !important; text-align: center !important; padding:20px; }
.thumbs-table tbody,
.thumbs-table tbody tr,
.thumbs-table tbody td { width:100%; display:block !important; }
.thumbs-table tbody tr { display: inline-block !important; width: 300px !important; margin:10px !important;
    border:1px solid silver !important; background:white !important; }
.thumbs-table tbody td { text-align:center !important; border:0 !important; border-bottom:1px solid silver !important;
    min-height:38px !important; }
.thumbs-table tbody td:last-child { border-bottom:0 !important; }

tr.dragged { box-shadow:0 0 5px rgba(125,125,125,0.75); position:relative; }

.crud-header { border-bottom:1px solid #ebebeb !important; }
.filters-form, .filters-column { display: inline-block; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
(function () {
    $('#save-filter-modal .cancel').on('click', function (e) {
        $(this).closest('.modal').modal('hide');
    });
    var checks = $('#<?= $id ?>__column_chooser')
        .find(':checkbox').change(function () {
            var hidden = [];
            $(this).closest('.column-chooser').find(':checkbox').each(function (i) {
                var column = $(this).prev().attr('name').replace('column_chooser_', '');
                if (!$(this).prop('checked')) {
                    hidden.push(column);
                }
                $('#<?= $id ?>').find('td[data-column="'+column+'"], th[data-column="'+column+'"]')
                        .css('display', $(this).prop('checked') ? 'table-cell' : 'none');
            });
            window.localStorage.setItem(window.location.pathname + '::column_chooser', JSON.stringify(hidden));
            $('#<?= $id ?>').find('th:last-child')
                .find('button.filter')[hidden.length ? 'addClass' : 'removeClass']('orange')
        });
    var tmp = window.localStorage.getItem(window.location.pathname + '::column_chooser');
    if (!tmp) {
        tmp = '<?= json_encode($hidden) ?>';
    }
    if (tmp = JSON.parse(tmp)) {
        tmp.forEach(function (v) {
            $('#<?= $id ?>__column_chooser').find('[name="column_chooser_'+v+'"]').val('0')
                .next().prop('checked', false);
        });
        checks.eq(0).change();
    }
    var table = $('#<?= $id ?>');
    table
        .on('click', 'td', function (e) {
            if (e.target.tagName === 'TD' && e.target.className.indexOf('operations') === -1) {
                var href = $(this).closest('tr').children('td').last().find('.button').not('.skip').eq(0).attr('href');
                if (href) {
                    window.location = href;
                }
            }
        })
        .on('click', '.state-button', function (e) {
            e.preventDefault();
            var buttons = $(this).parent().children('.state-button');
            var data = {};
            data[$(this).data('field')] = $(this).data('value');
            $.post(
                window.location.pathname.trim('/') + '/partial/' + $(this).closest('tr').data('id'),
                data
            )
                .done(function (data) {
                    // buttons.hide().each(function () {
                    //     if ($(this).data('value') != data.value) {
                    //         $(this).show();
                    //     }
                    // });
                    window.location.reload();
                })
                .fail(function () {
                    window.location.reload();
                });
        })
        .find('th > .filter').each(function () {
            $(this).popup({
                on: 'click',
                position: 'bottom right',
                popup : $('#' + $(this).data('popup'))
            });
        }).end()
        .find('.button.blank').attr('target', '_blank');

    var filters = $('#<?= $id ?>').closest('.grid').find('.filters-column');
    if (filters.length) {
        var moduleName = JSON.parse('<?= json_encode($tablename) ?>');
        var clientFilters = localStorage.getItem(moduleName + '.filters');
        var serverFilters = JSON.parse('<?= json_encode($filters) ?>');
        if (clientFilters) {
            clientFilters = JSON.parse(clientFilters);
        }
        if (!clientFilters) {
            clientFilters = [];
        }
        var isFiltered = $('#<?= $id ?>').prevAll('.attached.message').length > 0;
        var defaultFilter = <?= json_encode(
            $query,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
        ) ?>;
        defaultFilter = defaultFilter
            .split('&')
            .map(function (value) {
                return decodeURI(value);
            })
            .sort()
            .filter(function (value) {
                return ['p', 'l', 'd', 'o'].indexOf(value.split('=')[0]) === -1;
            });
        var currentFilter = window.location.search.substring(1).split('&')
            .map(function (value) {
                return decodeURI(value);
            })
            .sort()
            .filter(function (value) {
                return ['p', 'l', 'd', 'o'].indexOf(value.split('=')[0]) === -1;
            });
        currentFilter = $.unique(currentFilter.concat(defaultFilter).sort())
            .filter(function (value) { return !!value; });
        if (isFiltered) {
            var isClientFilter = false;
            var isServerFilter = false;
            var clientSearch = null;
            clientFilters.map(function (v) {
                var filter = v.search.substring(1).split('&')
                    .map(function (value) {
                        return decodeURI(value);
                    })
                    .sort()
                    .filter(function (value) {
                        return ['p', 'l', 'd', 'o'].indexOf(value.split('=')[0]) === -1;
                    });
                if (filter.join('&') === currentFilter.join('&')) {
                    isClientFilter = true;
                    clientSearch = v.search;
                    v.selected = true;
                }
                return v;
            });
            serverFilters.map(function (v) {
                var filter = v.search.substring(1).split('&')
                    .map(function (value) {
                        return decodeURI(value);
                    })
                    .sort()
                    .filter(function (value) {
                        return ['p', 'l', 'd', 'o'].indexOf(value.split('=')[0]) === -1;
                    });
                if (filter.join('&') === currentFilter.join('&')) {
                    isServerFilter = true;
                    clientSearch = v.search;
                    v.selected = true;
                }
                return v;
            });
            if (!isClientFilter && !isServerFilter) {
                $('#<?= $id ?>').prevAll('.attached.message').find('.button')
                    .after('<button class="ui save-filter-button teal right floated labeled icon button">'+
                    '<i class="save icon"></i> <?= $this->e($intl("crud.save_filter")) ?></button>');
                $('#<?= $id ?>').prevAll('.attached.message').find('.save-filter-button').on('click', function (e) {
                    e.preventDefault();
                    $('#save-filter-modal').modal('show');
                });
                $('#save-filter-modal').find('.submit').click(function (e) {
                    e.preventDefault();
                    var input = $(this).closest('.form').find('input[type=text]');
                    if (!input.val()) {
                        input.closest('.field').addClass('error');
                        return;
                    }
                    clientFilters.push({
                        name : input.val(),
                        search : window.location.search
                    });
                    localStorage.setItem(moduleName + '.filters', JSON.stringify(clientFilters));
                    window.location.reload();
                })
            }
            if (isClientFilter) {
                $('#<?= $id ?>').prevAll('.attached.message').find('.button')
                    .after('<button class="ui remove-filter-button red right floated labeled icon button">'+
                    '<i class="trash icon"></i> <?= $this->e($intl("crud.remove_filter")) ?></button>');
                $('#<?= $id ?>').prevAll('.attached.message').find('.remove-filter-button').on('click', function (e) {
                    e.preventDefault();
                    clientFilters = clientFilters.filter(function (v) {
                        return v.search !== clientSearch;
                    })
                    localStorage.setItem(moduleName + '.filters', JSON.stringify(clientFilters));
                    window.location.reload();
                });
            }
        }
        if (clientFilters.length || serverFilters.length) {
            var select = $('<select class="search">');
            select.append('<option value=""><?= $this->e($intl("crud.choose_filter")) ?></option>');
            select.append(serverFilters.map(function (v) {
                return $('<option>').text(v.name).attr('value', v.search);
            }));
            select.append(clientFilters.map(function (v) {
                return $('<option>').text(v.name).attr('value', v.search);
            }));
            if (isClientFilter) {
                select.val(clientSearch);
            }
            if (isServerFilter) {
                select.val(clientSearch);
            }
            filters.prepend(select);
            select.change(function () {
                window.location.href = $(this).val() || '?';
            });
            select.dropdown();
        }
    }
    var uri = URI(window.location.href.toString()),
        que = uri.search(true);
    $('#<?= $id ?>')
        .on('click', '.quick-filter', function (e) {
            var tmp = URI(window.location.href.toString())
                .removeQuery($(this).data('column'))
                .removeQuery(new RegExp('^' + $(this).data('column').replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&") + '\\['));
            if ($(this).hasClass('filter')) {
                tmp.addQuery($(this).data('column'), $(this).data('value'));
            }
            window.location = tmp;
        })
        .find('.quick-filter').each(function () {
            if (que[$(this).data('column')] === $(this).data('value').toString()) {
                $(this).toggleClass('filter remove')
            }
        });

    function orderColumns(columns) {
        var order = [];
        var table = $('#<?= $id ?>');
        var thead = table.find('thead');
        var trows = table.find('tr');
        var index;
        columns.reverse().forEach(function (v) {
            var th = thead.find('th[data-column="'+v+'"]');
            if (th.length) {
                index = th.index();
                trows.each(function () {
                    $(this).children('td, th').eq(index).prependTo(this);
                });
            }
        });
        table.find('th > .filter').each(function () {
            $(this).popup({
                on: 'click',
                position: 'bottom right',
                popup : $('#' + $(this).data('popup'))
            });
        });
    }
    // columns drag'n'drop
    var isdrg = 0,
        initx = false,
        inity = false,
        ofstx = false,
        ofsty = false,
        holdr = false,
        elmnt = false;
        container = $('#<?= $id ?>__column_chooser');
    container
        .on('mousedown', '.row', function (e) {
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
                    var targt = $(e.target).closest('.row'), i, j;
                    if(targt.length && targt[0] !== elmnt[0] && targt.closest('#<?= $id ?>__column_chooser').length) {
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
                    // update table
                    var columns = [];
                    container.find(':checkbox').each(function () {
                        var column = $(this).prev().attr('name').replace('column_chooser_', '');
                        columns.push(column);
                    });
                    window.localStorage.setItem(
                        window.location.pathname + '::column_chooser_order',
                        JSON.stringify(columns)
                    );
                    orderColumns(columns);
                }
                isdrg = 0;
                initx = false;
                inity = false;
                elmnt = false;
                holdr = false;
            }
        });
    var columns = window.localStorage.getItem(window.location.pathname + '::column_chooser_order');
    if (columns && (columns = JSON.parse(columns))) {
        columns.reverse().forEach(function (v) {
            container.find('input[name="column_chooser_'+v+'"]').closest('.row').prependTo(container.find('.grid'));
        });
        orderColumns(columns.reverse());
    }
    container.find('.field')
        .prepend('<i class="right floated vertical ellipsis icon"></i>');

    $(window).on('resize', function () {
        $('.table-read').css('max-height', 'none')
            .removeClass('overflowing head last stuck ').closest('.segment').removeClass('fixed-content');
        if ($(window).width() > 767 &&
            $('.table-read').outerWidth() > $('.table-read').closest('.segment').outerWidth()
        ) {
            $('.table-read').addClass('overflowing head last stuck ').closest('.segment').addClass('fixed-content')
                .prepend($('.table-read').closest('.segment').closest('.content').children('.ui.message'));
            var total = $('.crud-header').outerHeight() + 45;
            $('.table-read').siblings().not('.filter-popup, script, style, .modal').each(function (i, v) {
                total += $(v).outerHeight();
            });
            $('.table-read').css('max-height', ($('.fixed-content').outerHeight() - total) + 'px');
        }
    }).trigger('resize');

    $('.filter-modifier select').on('change', function () {
        $(this).closest('.form').find('.filter-modified').attr('name', $(this).val())
    });

    $('.thumb-button')
        .on('click', function (e) {
            e.preventDefault();
            $('.table-read').toggleClass('thumbs-table');
            window.localStorage.setItem(
                window.location.pathname + '::thumbs',
                $('.table-read').hasClass('thumbs-table') ? 'Y' : 'N'
            );
        });
    if (
        window.localStorage.getItem(window.location.pathname + '::thumbs') === 'Y' ||
        window.location.search.indexOf('?thumbs=') !== -1 ||
        window.location.search.indexOf('&thumbs=') !== -1
    ) {
        $('.thumb-button').trigger('click');
    }
}());
</script>
<?php if ($table->hasAttr('data-dnd') && !$table->hasClass('table-filtered')) : ?>
<script nonce="<?= $this->e($cspNonce) ?>">
(function () {
    var table = $('#<?= $id ?>');
    var isdrg = 0,
        initx = false,
        inity = false,
        ofstx = false,
        ofsty = false,
        holdr = false,
        elmnt = false;
        container = table.children('tbody').eq(0);
    container
        .on('mousedown', '.ellipsis', function (e) {
            elmnt = $(this).closest('tr');
            try {
                e.currentTarget.unselectable = "on";
                e.currentTarget.onselectstart = function () { return false; };
                if(e.currentTarget.style) { e.currentTarget.style.MozUserSelect = "none"; }
            } catch (err) { }
            holdr = false;
            initx = e.pageX;
            inity = e.pageY;
            elmnt = $(this).closest('tr');
            var o = elmnt.offset();
            ofstx = e.pageX - o.left;
            ofsty = e.pageY - o.top;
            isdrg = 1;
            elmnt.addClass('dragged');
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
                    var targt = $(e.target).closest('tr'), i, j;
                    if (targt.length &&
                        targt[0] !== elmnt[0] &&
                        targt.closest('tbody')[0] === container[0]
                    ) {
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
                    var order = [];
                    table.children('tbody').children().each(function (i, v) {
                        order.push(v.getAttribute('data-id'));
                    });
                    $.post(table.data('dnd'), { 'order' : JSON.stringify(order) })
                        .fail(function () {
                            window.location.reload();
                        });
                }
                elmnt.removeClass('dragged');
                isdrg = 0;
                initx = false;
                inity = false;
                elmnt = false;
                holdr = false;
            }
        });
}());
</script>
<?php endif ?>
