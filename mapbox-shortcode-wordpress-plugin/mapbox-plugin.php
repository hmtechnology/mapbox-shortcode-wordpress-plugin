<?php
/*
Plugin Name: _Mapbox Plugin
Description: Allows embedding with shortcode a mapbox with info window popup.
Version: 1.0
Author: hmtechnology
Author URI: https://github.com/hmtechnology
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
Plugin URI: https://github.com/hmtechnology/mapbox-shortcode-wordpress-plugin
*/

// Add Mapbox settings page to the admin menu.
function mapbox_add_admin_page() {
    add_menu_page(
        'Impostazioni Mapbox',
        'Mapbox',
        'manage_options',
        'mapbox-settings',
        'mapbox_render_settings_page',
        'dashicons-location'
    );
}
add_action('admin_menu', 'mapbox_add_admin_page');

// Function to render the settings page in the admin panel
function mapbox_render_settings_page() {
    ?>
    <style>
	input[type="text"] {
		margin-bottom: 5px;
	}
	input[type="range"] {
            width: 150px;
        }
    </style>
    <div class="wrap">
        <h2>Impostazioni Mapbox</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('mapbox_settings');
            do_settings_sections('mapbox-settings');
            ?>
            <h3>Impostazioni Mappa</h3>
			<p>Puoi definire uno stile specifico per la visualizzazione, il livello di zoom, l'inclinazione e la rotazione della mappa.</p>
            <table class="form-table">
				<tr valign="top">
                    <th scope="row">Stile della mappa</th>
                    <td>
                        <?php
                        $selected_style = get_option('mapbox_style', 'mapbox://styles/mapbox/outdoors-v12');
                        $styles = array(
                            'mapbox://styles/mapbox/outdoors-v12' => 'Outdoors',
							'mapbox://styles/mapbox/streets-v12' => 'Streets',
							'mapbox://styles/mapbox/satellite-v9' => 'Satellite',
							'mapbox://styles/mapbox/light-v11' => 'Light',
							'mapbox://styles/mapbox/dark-v11' => 'Dark',
							'mapbox://styles/mapbox/satellite-streets-v12' => 'Satellite Streets',
							'mapbox://styles/mapbox/navigation-day-v1' => 'Navigation Day',
							'mapbox://styles/mapbox/navigation-night-v1' => 'Navigation Night',
                        );
                        ?>
                        <select name="mapbox_style">
                            <?php
                            foreach ($styles as $style => $label) {
                                echo '<option value="' . esc_attr($style) . '" ' . selected($selected_style, $style, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
				<tr valign="top">
					<th scope="row">Zoom predefinito (0-22)</th>
					<td>
						<?php
						$default_zoom = esc_attr(get_option('mapbox_zoom', 18));
						?>
						<input type="range" name="mapbox_zoom" id="mapbox-zoom-range" min="0" max="22" value="<?php echo $default_zoom; ?>" oninput="updateRangeAndText(this, 'mapbox-zoom-text')">
						<input type="text" name="mapbox_zoom_text" id="mapbox-zoom-text" value="<?php echo $default_zoom; ?>" oninput="updateTextAndRange(this, 'mapbox-zoom-range')">
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Pitch predefinito (0-85)</th>
					<td>
						<?php
						$default_pitch = esc_attr(get_option('mapbox_pitch', 60));
						?>
						<input type="range" name="mapbox_pitch" id="mapbox-pitch-range" min="0" max="85" value="<?php echo $default_pitch; ?>" oninput="updateRangeAndText(this, 'mapbox-pitch-text')">
						<input type="text" name="mapbox_pitch_text" id="mapbox-pitch-text" value="<?php echo $default_pitch; ?>" oninput="updateTextAndRange(this, 'mapbox-pitch-range')">
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Bearing predefinito (0-360)</th>
					<td>
						<?php
						$default_bearing = esc_attr(get_option('mapbox_bearing', 0));
						?>
						<input type="range" name="mapbox_bearing" id="mapbox-bearing-range" min="0" max="360" value="<?php echo $default_bearing; ?>" oninput="updateRangeAndText(this, 'mapbox-bearing-text')">
						<input type="text" name="mapbox_bearing_text" id="mapbox-bearing-text" value="<?php echo $default_bearing; ?>" oninput="updateTextAndRange(this, 'mapbox-bearing-range')">
					</td>
				</tr>
            </table>
			<h3>Impostazioni Iframe</h3>
			<p>Definisci la larghezza e l'altezza dell'iframe contenente la mappa.</p>
			<table class="form-table">
				<tr>
					<th scope="row">Larghezza Iframe</th>
					<td>
						<input type="number" step="1" name="mapbox_iframe_width_value" value="<?php echo esc_attr(get_option('mapbox_iframe_width_value', 100)); ?>" />
						<select name="mapbox_iframe_width_units">
							<option value="%" <?php selected(get_option('mapbox_iframe_width_units', '%'), '%'); ?>>%</option>
							<option value="px" <?php selected(get_option('mapbox_iframe_width_units', '%'), 'px'); ?>>px</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">Altezza Iframe</th>
					<td>
						<input type="number" step="1" name="mapbox_iframe_height_value" value="<?php echo esc_attr(get_option('mapbox_iframe_height_value', 400)); ?>" />
						<select name="mapbox_iframe_height_units">
							<option value="%" <?php selected(get_option('mapbox_iframe_height_units', 'px'), '%'); ?>>%</option>
							<option value="px" <?php selected(get_option('mapbox_iframe_height_units', 'px'), 'px'); ?>>px</option>
						</select>
					</td>
				</tr>
			</table>
	        <button type="button" class="button" id="mapbox-reset-options">Resetta Opzioni Predefinite</button>
            <?php
            submit_button();
            ?>
            <div id="loading-modal" style="display: none;">
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); text-align: center; padding-top: 20%;">
                    <p style="font-size: 20px;">Aggiunta Nuovo Marker in corso...</p>
                </div>
            </div>
        </form>
    </div>
	<script>
		// Manage loading window
		jQuery(document).ready(function ($) {
			$('#mapbox-add-marker').off('click').on('click', function () {
				$('#loading-modal').show();

				var index = $('#mapbox-markers-container .mapbox-marker').length;
				$.ajax({
					url: ajaxurl,
					data: {
						action: 'mapbox_add_marker_field',
						index: index,
					},
					success: function (response) {
						$('#mapbox-markers-container').append(response);
						$('#loading-modal').hide();
					}
				});
			});
		});
	</script>
	<script>
		// Manage Reset button
		jQuery(document).ready(function ($) {
			$('#mapbox-reset-options').on('click', function () {
				var confirmReset = confirm('Sei sicuro di voler ripristinare le impostazioni predefinite?');
				if (confirmReset) {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'mapbox_reset_options',
							security: '<?php echo wp_create_nonce("mapbox-reset-options"); ?>',
						},
						success: function () {
							alert('Opzioni ripristinate con successo!');
							location.reload();
						}
					});
				}
			});
		});
	</script>
	<script>
		function updateRangeAndText(input, textId) {
			document.getElementById(textId).value = input.value;
		}

		function updateTextAndRange(input, rangeId) {
			document.getElementById(rangeId).value = input.value;
		}
	</script>
    <?php
}

// Add AJAX action for resetting options
add_action('wp_ajax_mapbox_reset_options', 'mapbox_reset_options');

function mapbox_reset_options() {
    check_ajax_referer('mapbox-reset-options', 'security');

    delete_option('mapbox_markers');
    delete_option('mapbox_token');
    delete_option('mapbox_popup_background');
    delete_option('mapbox_mapmap_background');
    delete_option('mapbox_h2_color');
    delete_option('mapbox_zoom');
    delete_option('mapbox_pitch');
    delete_option('mapbox_bearing');
    delete_option('mapbox_iframe_width_value');
    delete_option('mapbox_iframe_height_value');
    delete_option('mapbox_iframe_width_units');
    delete_option('mapbox_iframe_height_units');	
    delete_option('mapbox_style');

    wp_send_json_success();
}


// Register settings and fields for the admin panel
function mapbox_register_settings() {
    register_setting('mapbox_settings', 'mapbox_markers', 'mapbox_validate_markers');
    register_setting('mapbox_settings', 'mapbox_token'); // Aggiunto per il campo del token Mapbox
    add_settings_section('mapbox_section', 'Marker', 'mapbox_section_callback', 'mapbox-settings');
    add_settings_field('mapbox_markers_field', 'Configura i Marker', 'mapbox_markers_field_callback', 'mapbox-settings', 'mapbox_section');
    add_settings_field('mapbox_token_field', 'Inserisci il tuo token Mapbox', 'mapbox_token_field_callback', 'mapbox-settings', 'mapbox_section');
    register_setting('mapbox_settings', 'mapbox_popup_background');
    register_setting('mapbox_settings', 'mapbox_mapmap_background');
    register_setting('mapbox_settings', 'mapbox_h2_color');
    add_settings_section('mapbox_style_section', 'Stile Popup', 'mapbox_style_section_callback', 'mapbox-settings');
    add_settings_field('mapbox_popup_background_field', 'Colore del bordo della popup', 'mapbox_popup_background_field_callback', 'mapbox-settings', 'mapbox_style_section');
    add_settings_field('mapbox_mapmap_background_field', 'Colore di sfondo della popup', 'mapbox_mapmap_background_field_callback', 'mapbox-settings', 'mapbox_style_section');
    add_settings_field('mapbox_h2_color_field', 'Colore per titolo popup', 'mapbox_h2_color_field_callback', 'mapbox-settings', 'mapbox_style_section');
    register_setting('mapbox_settings', 'mapbox_zoom', array(
        'type' => 'number',
        'default' => 18,
    ));
    register_setting('mapbox_settings', 'mapbox_pitch', array(
        'type' => 'number',
        'default' => 60,
    ));
    register_setting('mapbox_settings', 'mapbox_bearing', array(
        'type' => 'number',
        'default' => 0,
    ));
	register_setting('mapbox_settings', 'mapbox_iframe_width_value', array(
		'type' => 'number',
		'default' => 100,
		'sanitize_callback' => 'absint', // Usa 'absint' per numeri interi positivi
	));

	register_setting('mapbox_settings', 'mapbox_iframe_width_units', array(
		'type' => 'string',
		'default' => '%',
		'sanitize_callback' => 'sanitize_text_field',
	));

	register_setting('mapbox_settings', 'mapbox_iframe_height_value', array(
		'type' => 'number',
		'default' => 400,
		'sanitize_callback' => 'absint', // Usa 'absint' per numeri interi positivi
	));

	register_setting('mapbox_settings', 'mapbox_iframe_height_units', array(
		'type' => 'string',
		'default' => 'px',
		'sanitize_callback' => 'sanitize_text_field',
	));

    register_setting('mapbox_settings', 'mapbox_style', array(
        'type' => 'string',
        'default' => 'mapbox://styles/mapbox/outdoors-v12',
        'sanitize_callback' => 'mapbox_sanitize_map_style',
    ));
}

add_action('admin_init', 'mapbox_register_settings');

// Custom sanitization function for map style
function mapbox_sanitize_map_style($input) {
    $allowed_styles = array(
		'mapbox://styles/mapbox/outdoors-v12',
		'mapbox://styles/mapbox/streets-v12',
		'mapbox://styles/mapbox/satellite-v9',
		'mapbox://styles/mapbox/light-v11',
		'mapbox://styles/mapbox/dark-v11',
		'mapbox://styles/mapbox/satellite-streets-v12',
		'mapbox://styles/mapbox/navigation-day-v1',
		'mapbox://styles/mapbox/navigation-night-v1',
    );

    return in_array($input, $allowed_styles) ? $input : 'mapbox://styles/mapbox/outdoors-v12';
}

// Callback for the marker field
function mapbox_markers_field_callback() {
    $markers = get_option('mapbox_markers');
    ?>
    <div id="mapbox-markers-container">
        <?php
        if ($markers) {
            foreach ($markers as $index => $marker) {
                mapbox_render_marker_fields($index, $marker);
            }
        } else {
            mapbox_render_marker_fields(0, array());
        }
        ?>
    </div>
    <button type="button" class="button" id="mapbox-add-marker">Aggiungi Nuovo Marker</button>
    <script>
        jQuery(document).ready(function ($) {
            $('#mapbox-add-marker').click(function () {
                var index = $('#mapbox-markers-container .mapbox-marker').length;
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'mapbox_add_marker_field',
                        index: index,
                    },
                    success: function (response) {
                        $('#mapbox-markers-container').append(response);
                    }
                });
            });
        });
    </script>
    <?php
}

function mapbox_render_marker_fields($index, $marker) {
    ?>
	<div class="mapbox-marker" style="margin-bottom: 25px; border: 1px solid #999; padding: 0px 20px;background: #f9f9f9;">
		<h3>Marker n. <?php echo $index + 1; ?></h3>
		<input type="text" name="mapbox_markers[<?php echo $index; ?>][location_name]" placeholder="Nome della Location" value="<?php echo isset($marker['location_name']) ? esc_attr($marker['location_name']) : ''; ?>" />
		<br>
		<input type="text" name="mapbox_markers[<?php echo $index; ?>][street]" placeholder="Via" value="<?php echo isset($marker['street']) ? esc_attr($marker['street']) : ''; ?>" />
		<input type="text" name="mapbox_markers[<?php echo $index; ?>][postcode]" placeholder="Cap" value="<?php echo isset($marker['postcode']) ? esc_attr($marker['postcode']) : ''; ?>" />
		<input type="text" name="mapbox_markers[<?php echo $index; ?>][city]" placeholder="Città" value="<?php echo isset($marker['city']) ? esc_attr($marker['city']) : ''; ?>" />
		<input type="text" name="mapbox_markers[<?php echo $index; ?>][state]" placeholder="Provincia" value="<?php echo isset($marker['state']) ? esc_attr($marker['state']) : ''; ?>" />
		<input type="text" name="mapbox_markers[<?php echo $index; ?>][country]" placeholder="Stato" value="<?php echo isset($marker['country']) ? esc_attr($marker['country']) : ''; ?>" />
		<br>
		<input type="text" name="mapbox_markers[<?php echo $index; ?>][latitude]" placeholder="Latitudine" value="<?php echo isset($marker['latitude']) ? esc_attr($marker['latitude']) : ''; ?>" />
		<input type="text" name="mapbox_markers[<?php echo $index; ?>][longitude]" placeholder="Longitudine" value="<?php echo isset($marker['longitude']) ? esc_attr($marker['longitude']) : ''; ?>" />
		<br>
		<input type="text" name="mapbox_markers[<?php echo $index; ?>][logo_url]" placeholder="URL del Logo" value="<?php echo isset($marker['logo_url']) ? esc_attr($marker['logo_url']) : ''; ?>" />
		<input type="button" value="Carica Logo" class="button mapbox-upload-logo" data-target="#mapbox-logo-preview-<?php echo $index; ?>" />
		<div id="mapbox-logo-preview-<?php echo $index; ?>" class="mapbox-logo-preview">
			<?php if (!empty($marker['logo_url'])) : ?>
				<img src="<?php echo esc_attr($marker['logo_url']); ?>" alt="Marker Logo" style="max-width: 100px; max-height: 100px; margin-top: 20px;" />
			<?php endif; ?>
		</div>
		<br>
		<button type="button" class="button mapbox-remove-marker">Rimuovi Marker</button>
		<br><br>
	</div>
    <script>
        jQuery(document).ready(function ($) {
            $(document).on('click', '.mapbox-remove-marker', function () {
                $(this).closest('.mapbox-marker').remove();
            });
        });
    </script>
    <?php
}

// Enqueue media uploader scripts
function enqueue_media_uploader() {
    wp_enqueue_media();
    wp_enqueue_script('media-upload');
}
add_action('admin_enqueue_scripts', 'enqueue_media_uploader');

// Allow Logo upload
function mapbox_media_upload_script() {
    ?>
    <script>
        jQuery(document).ready(function ($) {
            $(document).on('click', '.mapbox-upload-logo', function () {
                var target = $($(this).data('target'));
                var logoUrlInput = $(this).prev('input[type="text"]');
                var custom_uploader = wp.media({
                    title: 'Carica Logo',
                    button: {
                        text: 'Inserisci Logo',
                    },
                    multiple: false,
                });

                custom_uploader.on('select', function () {
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    target.html('<img src="' + attachment.url + '" alt="Marker Logo" style="max-width: 100px; max-height: 100px;margin-top: 20px;" />');
                    logoUrlInput.val(attachment.url);
                });

                custom_uploader.open();
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'mapbox_media_upload_script');

// Callback to validate the marker field
function mapbox_validate_markers($input) {
    $markers = array();

    foreach ($input as $marker) {
        $validated_marker = array(
            'location_name' => sanitize_text_field($marker['location_name']),
            'street' => sanitize_text_field($marker['street']),
            'postcode' => sanitize_text_field($marker['postcode']),
            'city' => sanitize_text_field($marker['city']),
            'state' => sanitize_text_field($marker['state']),
            'country' => sanitize_text_field($marker['country']),
            'latitude' => sanitize_text_field($marker['latitude']),
            'longitude' => sanitize_text_field($marker['longitude']),
            'logo_url' => empty($marker['logo_url']) ? plugins_url('marker-map.png', __FILE__) : esc_url_raw($marker['logo_url']),
		);

        $markers[] = $validated_marker;
    }

    return $markers;
}

// Add AJAX action to add a marker field
function mapbox_add_marker_field() {
    $index = isset($_GET['index']) ? intval($_GET['index']) : 0;
    mapbox_render_marker_fields($index, array());
    wp_die();
}
add_action('wp_ajax_mapbox_add_marker_field', 'mapbox_add_marker_field');

// Callback for the settings section
function mapbox_section_callback() {
    echo 'Configura i marker sulla mappa.';
	echo '<p class="description">Nota: Puoi trovare le coordinate geografiche a <a href="https://www.gps-coordinates.net/" target="_blank">questo link</a>.</p>';
}

// Callback for the Mapbox token field
function mapbox_token_field_callback() {
    $token = get_option('mapbox_token');
    ?>
    <input type="text" name="mapbox_token" placeholder="Token Mapbox" value="<?php echo esc_attr($token); ?>" />
    <p class="description">Se non hai un token Mapbox, puoi ottenerne uno <a href="https://account.mapbox.com/access-tokens/create" target="_blank">qui</a>.</p>
    <?php
}

// Callback for the popup border color field
function mapbox_popup_background_field_callback() {
    $background = get_option('mapbox_popup_background', '#c6c6c6');
    ?>
    <input type="text" class="color-field" name="mapbox_popup_background" value="<?php echo esc_attr($background); ?>" />
    <script>
        jQuery(document).ready(function($){
            $('.color-field').wpColorPicker();
        });
    </script>
    <?php
}

// Callback for the popup background color field
function mapbox_mapmap_background_field_callback() {
    $background = get_option('mapbox_mapmap_background', '#fff');
    ?>
    <input type="text" class="color-field" name="mapbox_mapmap_background" value="<?php echo esc_attr($background); ?>" />
    <script>
        jQuery(document).ready(function($){
            $('.color-field').wpColorPicker();
        });
    </script>
    <?php
}

// Callback for the popup title color field
function mapbox_h2_color_field_callback() {
    $color = get_option('mapbox_h2_color', '#ff0000');
    ?>
    <input type="text" class="color-field" name="mapbox_h2_color" value="<?php echo esc_attr($color); ?>" />
    <script>
        jQuery(document).ready(function($){
            $('.color-field').wpColorPicker();
        });
    </script>
    <?php
}

// Callback for the popup style section
function mapbox_style_section_callback() {
    echo 'Puoi personalizzare lo stile della popup definendo colore del bordo, dello sfondo e del titolo.';
}

// Add support for SVG file upload
add_filter('upload_mimes', 'allow_svg_upload');
function allow_svg_upload($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}

// Actions to perform when the plugin is activated
function mapbox_plugin_activation() {
    $default_markers = array(
        array(
            'location_name' => '',
            'street'        => '',
            'postcode'      => '',
            'city'          => '',
            'state'         => '',
            'country'       => '',
            'latitude'      => '',
            'longitude'     => '',
            'logo_url'      => '',
        )
    );
    add_option('mapbox_markers', $default_markers);
    add_option('mapbox_token', '');
}
register_activation_hook(__FILE__, 'mapbox_plugin_activation');

// Function to render the map via shortcode
function mapbox_render_map_shortcode($atts) {
    $markers = get_option('mapbox_markers');
    $token = get_option('mapbox_token');

    $zoom = get_option('mapbox_zoom', 18);
    $pitch = get_option('mapbox_pitch', 60);
    $bearing = get_option('mapbox_bearing', 0);
    $style = get_option('mapbox_style', 'mapbox://styles/mapbox/outdoors-v12');
	$iframe_width_value = get_option('mapbox_iframe_width_value', 100);
	$iframe_width_units = get_option('mapbox_iframe_width_units', '%');
	$iframe_height_value = get_option('mapbox_iframe_height_value', 400);
	$iframe_height_units = get_option('mapbox_iframe_height_units', 'px');

    if (!empty($markers) && $token) {
        $output = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Map</title>
                <meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no">
                <link href="https://api.mapbox.com/mapbox-gl-js/v2.10.0/mapbox-gl.css" rel="stylesheet">
                <script src="https://api.mapbox.com/mapbox-gl-js/v2.10.0/mapbox-gl.js"></script>
                <style>
                    body { margin: 0; padding: 0; }
    				#map { position: absolute; top: 0; bottom: 0; width: ' . esc_attr($iframe_width_value) . $iframe_width_units . '; height: ' . esc_attr($iframe_height_value) . $iframe_height_units . '; }
				</style>
                <style>
                    .mapmap h2, .mapmap p {
                        font-family: Verdana;
                        text-align: center;
                    }

					.mapmap h2 {
                        font-size: 18px;
                        letter-spacing: 1px;
                        margin-top: 5px;
                        color: ' . esc_js(get_option('mapbox_h2_color')) . ';
                    }

                    .mapmap p {
                        letter-spacing: 0.2px;
                        margin-top: 7px;
                    }

                    .mapboxgl-popup-content {
						box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5)
                    }
                    ';

					foreach ($markers as $index => $marker) {
						$output .= '
										#marker_' . $index . ' {
											background-image: url("' . esc_js($marker['logo_url']) . '");
											background-size: cover;
											width: 50px;
											height: 50px;
											border-radius: 50%;
											cursor: pointer;
											transform: translate(-50%, -50%);
										}
									';
					}

					$output .= '
					
                    .mapboxgl-popup {
                        max-width: 275px;
                    }

                    .mapboxgl-popup-close-button:focus {
                        outline:none; 
                    }

                    .mapboxgl-popup-close-button {
                        font-size: 1.5rem;
						transition: color 0.1s ease-in-out;
                    }
					
					.mapboxgl-popup-close-button:hover {
						color: #ff0000;
					}
                    .mapboxgl-popup-content {
                        background: ' . esc_js(get_option('mapbox_popup_background')) . ';
                        padding: 1px;
                    }

                    .mapmap {
                        background: ' . esc_js(get_option('mapbox_mapmap_background')) . ';
                        color: #333;
                        padding: 1rem;
                    }
                </style>
            </head>
            <body>
                <div id="map"></div>
                <script>
                    mapboxgl.accessToken = \'' . esc_js($token) . '\';

                    const map = new mapboxgl.Map({
                        container: \'map\',
                        style: \'' . esc_js($style) . '\',
                        center: [' . esc_js($markers[0]['longitude']) . ', ' . esc_js($markers[0]['latitude']) . '],
						pitch: ' . esc_js($pitch) . ',
						zoom: ' . esc_js($zoom) . ',
						bearing: ' . esc_js($bearing) . '
                    });
					
					map.addControl(new mapboxgl.NavigationControl());
                    ';

					foreach ($markers as $index => $marker) {
						$output .= '
									const coordinates_' . $index . ' = [' . esc_js($marker['longitude']) . ', ' . esc_js($marker['latitude']) . '];
									const locationName_' . $index . ' = \'' . esc_js($marker['location_name']) . '\';
									const street_' . $index . ' = \'' . esc_js($marker['street']) . '\';
									const postcode_' . $index . ' = \'' . esc_js($marker['postcode']) . '\';
									const city_' . $index . ' = \'' . esc_js($marker['city']) . '\';
									const state_' . $index . ' = \'' . esc_js($marker['state']) . '\';
									const country_' . $index . ' = \'' . esc_js($marker['country']) . '\';

									const popupContent_' . $index . ' = "<div class=\"mapmap\">" +
										"<h2 style=\"color: ' . esc_js(get_option('mapbox_h2_color')) . ';padding-bottom: 10px;margin-bottom: -2px;\">" + locationName_' . $index . ' + "</h2>" +
										"<div style=\"border-top: 1px solid #2f3490;width: 30%;text-align: center;margin: 4px auto 0 auto;\"></div>" +
										"<p><strong>" + street_' . $index . ' + "</strong>" +
										"<br>" + postcode_' . $index . ' + " " + city_' . $index . ' + " (" + state_' . $index . ' + "), " + country_' . $index . ' + "</p>" +
										"</div>";


									const popup_' . $index . ' = new mapboxgl.Popup({ offset: 25 }).setHTML(popupContent_' . $index . ');

									const el_' . $index . ' = document.createElement(\'div\');
									el_' . $index . '.id = \'marker_' . $index . '\';

									new mapboxgl.Marker(el_' . $index . ')
										.setLngLat(coordinates_' . $index . ')
										.setPopup(popup_' . $index . ')
										.addTo(map);
									';
					}

					$output .= '
                </script>
            </body>
            </html>
        ';

    return $output;
	} else {
		return 'I marker o il token non sono validi. Assicurati di aver inserito tutte le informazioni richieste nel pannello di amministrazione.';
	}
}


// Function to return the map output in an iframe
function mapbox_map_shortcode($atts) {
	$width_value = get_option('mapbox_iframe_width_value', 100);
	$width_units = get_option('mapbox_iframe_width_units', '%');
	$height_value = get_option('mapbox_iframe_height_value', 400);
	$height_units = get_option('mapbox_iframe_height_units', 'px');


    $map_html = mapbox_render_map_shortcode($atts);
	return '<div style="text-align:center">
				<iframe srcdoc="' . esc_attr($map_html) . '" style="width:' . esc_attr($width_value) . $width_units . '; height:' . esc_attr($height_value) . $height_units . '; border:none;box-shadow: 0 0 10px rgba(0, 0, 0, 0.1)"></iframe>
			</div>';
}
add_shortcode('mapbox_map', 'mapbox_map_shortcode');

// Add wp_color_picker scripts and styles only on the settings page
function load_color_picker_scripts($hook) {
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
}
add_action('admin_enqueue_scripts', 'load_color_picker_scripts');