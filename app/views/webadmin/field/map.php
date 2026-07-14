<?php
/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var \vakata\intl\Intl $intl
 * @var string $cspNonce
 */
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'map_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$defaults = [
    'height' => 400,
    'zoom'   => 10,
    'center' => [ 42.697, 23.321 ]
];
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
$options = array_merge($defaults, $field->getOptions());

if ($field->hasAttr('value') && ($temp = json_decode($field->getValue(), true))) {
    $field->setValue($temp);
} else {
    $field->delAttr('value');
}
?>
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
<input type="hidden"
    <?php
    if ($field->hasAttr('name')) {
        echo ' name="' . $this->e($field->getAttr('name')) . '" ';
    }
    if ($disabled) {
        echo ' disabled="disabled" ';
    }
    ?>
    value="<?= $field->hasAttr('value') ? $this->e(json_encode($field->getValue()) ?: '') : '' ?>"
>
<div id="<?= $this->e($field->getAttr('id')) ?>"></div>

<?php if (!$disabled) : ?>
    <?php if (isset($options['quicklinks']) && count($options['quicklinks'])) : ?>
        <div id="<?= $this->e($field->getAttr('id')) ?>_buttons">
        <?php foreach ($options['quicklinks'] as $link) : ?>
            <button class="ui small button"
                data-location='{"lat": <?=(float)$link["lat"]?>, "lng": <?=(float)$link["lng"]?> }'>
                <?= $this->e($link['name']) ?></button>
        <?php endforeach ?>
        </div>
    <?php endif ?>
<?php endif ?>
<style nonce="<?= $this->e($cspNonce) ?>">
.map-marker { background:blue; border-radius:6px !important; border:1px solid white; box-shadow:0 0 3px blue; }
#<?= $this->e($field->getAttr('id')) ?>_buttons { padding-top:5px; }
#<?= $this->e($field->getAttr('id')) ?> { height: <?= $this->e($options['height']) ?>px }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
(function () {
    var map = L
        .map('<?= $this->e($field->getAttr("id")) ?>', { scrollWheelZoom : false })
        .setView(
            [<?= $this->e($options['center'][0]) ?>, <?= $this->e($options['center'][1]) ?> ],
            <?= $this->e($options['zoom']) ?>
        );
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: ' - '
    }).addTo(map);
    var marker = null;
    <?php if ($field->hasAttr('value')) : ?>
        var path = L.polyline(
            [ [ <?= $this->e($field->getValue()['lat']) ?>, <?= $this->e($field->getValue()['lng']) ?> ] ]
        );
        map.fitBounds(path.getBounds());
        var marker = L.marker(
            [ <?= $this->e($field->getValue()['lat']) ?>, <?= $this->e($field->getValue()['lng']) ?> ],
            {
                icon: L.divIcon({
                    className: 'map-marker'
                })
            }
        ).addTo(map);
    <?php endif ?>
    <?php if (!$disabled) : ?>
    if (typeof google !== 'undefined' && google.maps) {
        new L.Control.GPlaceAutocomplete({
            callback : function (place) {
                if (!place.geometry) {
                    return;
                }
                map.setView(
                    [place.geometry.location.lat(), place.geometry.location.lng()],
                    map.getZoom() < 14 ? 14 : map.getZoom()
                );
                if (!marker) {
                    marker = L.marker(
                        [place.geometry.location.lat(),place.geometry.location.lng()],
                        {
                            icon: L.divIcon({
                                className: 'map-marker'
                            })
                        }
                    ).addTo(map);
                } else {
                    marker.setLatLng([place.geometry.location.lat(),place.geometry.location.lng()]);
                }
                $('#<?= $this->e($field->getAttr("id")) ?>').prev().val(
                    JSON.stringify({ lat : place.geometry.location.lat(), 'lng' : place.geometry.location.lng() })
                );
            }
        }).addTo(map);
    }
    map.on('click', function (e) {
        if (!e.latlng) {
            return;
        }
        if (!marker) {
            marker = L.marker(
                [ e.latlng.lat, e.latlng.lng ],
                {
                    icon: L.divIcon({
                        className: 'map-marker'
                    })
                }
            ).addTo(map);
        } else {
            marker.setLatLng(new L.LatLng(e.latlng.lat, e.latlng.lng));
        }
        $('#<?= $this->e($field->getAttr("id")) ?>')
            .prev().val(JSON.stringify({ lat : e.latlng.lat, 'lng' : e.latlng.lng }));
    });

    $('#<?= $this->e($field->getAttr("id")) ?>_buttons').on('click', 'button', function (e) {
        e.preventDefault();
        var location = $(this).data('location');
        map.setView(
            [location.lat, location.lng],
            map.getZoom() < 14 ? 14 : map.getZoom()
        );
        if (!marker) {
            marker = L.marker(
                [ location.lat, location.lng ],
                {
                    icon: L.divIcon({
                        className: 'map-marker'
                    })
                }
            ).addTo(map);
        } else {
            marker.setLatLng([ location.lat, location.lng ]);
        }
        $('#<?= $this->e($field->getAttr("id")) ?>').prev().val(
            JSON.stringify(location)
        );
    });
    <?php endif ?>
    $(document).on('keydown', '#<?= $this->e($field->getAttr("id")) ?>, .pac-container', function (e) {
        if (e.keyCode === 13) {
            e.preventDefault();
        }
    });
}());
</script>
