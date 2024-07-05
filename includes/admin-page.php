<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add menu item
add_action('admin_menu', 'gml_add_admin_menu');
function gml_add_admin_menu() {
    add_menu_page('Google Maps Locations', 'Maps Locations', 'manage_options', 'gml-locations', 'gml_display_admin_page', 'dashicons-location', 6);
     // Add sub-menu page for settings
     add_submenu_page(
        'gml-locations',          // Parent menu slug
        'Google Maps Settings',   // Page title
        'Settings',               // Menu title
        'manage_options',         // Capability
        'gml-locations-settings', // Menu slug
        'gml_settings_page'       // Callback function
    );
}

// Register settings
add_action('admin_init', 'gml_register_settings');

function gml_register_settings() {
    register_setting('gml_settings', 'gml_api_key');
    register_setting('gml_settings', 'gml_marker_image');
    register_setting('gml_settings', 'gml_marker_color');
    register_setting('gml_settings', 'gml_marker_border_color');
    register_setting('gml_settings', 'gml_marker_scale');
    register_setting('gml_settings', 'gml_zoom');
    register_setting('gml_settings', 'gml_start_lat');
    register_setting('gml_settings', 'gml_start_lng');
    register_setting('gml_settings', 'gml_map_id');
}


function gml_settings_page() {
    ?>
    <div class="wrap">
        <h1>Google Maps Locations Settings</h1>
        <p>You need to get the <a href="https://developers.google.com/maps/documentation/javascript/" target="_blank" rel="noopener noreferrer">API key</a> and the <a href="https://developers.google.com/maps/documentation/javascript/cloud-customization" target="_blank" rel="noopener noreferrer">map styling ID</a> from the Google Cloud Platform.<br>Both of these are <b>required to use the advanced markers functionality</b>.</p>
        <form method="post" action="options.php">
            <?php
            settings_fields('gml_settings');
            do_settings_sections('gml_settings');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Google Maps API Key<sup>*</sup></th>
                    <td><input type="text" name="gml_api_key" value="<?php echo esc_attr(get_option('gml_api_key')); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Map Styling ID<sup>*</sup></th>
                    <td><input type="text" name="gml_map_id" value="<?php echo esc_attr(get_option('gml_map_id')); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Marker Image URL</th>
                    <td><input type="text" name="gml_marker_image" value="<?php echo esc_attr(get_option('gml_marker_image')); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Marker Color</th>
                    <td><input type="text" name="gml_marker_color" value="<?php echo esc_attr(get_option('gml_marker_color')); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Marker Border Color</th>
                    <td><input type="text" name="gml_marker_border_color" value="<?php echo esc_attr(get_option('gml_marker_border_color')); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Marker Scale</th>
                    <td><input type="text" name="gml_marker_scale" value="<?php echo esc_attr(get_option('gml_marker_scale')); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Map Zoom</th>
                    <td><input type="text"  name="gml_zoom" value="<?php echo esc_attr(get_option('gml_zoom')); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Map Starting Latitude</th>
                    <td><input type="text" name="gml_start_lat" value="<?php echo esc_attr(get_option('gml_start_lat')); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Map Starting Longitude</th>
                    <td><input type="text" name="gml_start_lng" value="<?php echo esc_attr(get_option('gml_start_lng')); ?>" class="regular-text"/></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


function gml_display_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gml_locations';
    $locations = $wpdb->get_results("SELECT * FROM $table_name");
    ?>
    <div class="wrap">
        <h1>Google Maps Locations</h1>
        <p>Visit the <a href="/wp-admin/admin.php?page=gml-locations-settings">settings page</a> and fill in the <b>API key and Map ID fields first</b> - those are required.</p>
        <p>Then You can use the shortcode [gml_map] on any page to display the multi-marker map.</p>
        <h2>Add Location:</h2>
        <form id="gml-location-form">
        <table class="form-table">
            <input type="hidden" id="location-id" name="location-id" value="">
            <tr>
                <th scope="row"><label for="label">Name<sup>*</sup>:</label></th>
                <td><input type="text" id="label" name="label" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="label">Description<sup>*</sup>:</label></th>
                <td><input type="text" id="address" name="address" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="label">Website:</label></th>
                <td><input type="url" id="url" name="url" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="lat">Latitude<sup>*</sup>:</label></th>
                <td><input type="text" id="lat" name="lat" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="lng">Longitude<sup>*</sup>:</label></th>
                <td><input type="text" id="lng" name="lng" class="regular-text" required><br></td>
            </tr>
        </table>
        <button class="button button-primary" type="submit">Add Location</button>
        </form>
        <br><br>
        <h2>Existing Locations:</h2>
        <ul id="gml-locations-list">
            <?php foreach ($locations as $location): ?>
                <li data-id="<?php echo esc_attr($location->id); ?>">
                    <b><?php echo esc_html($location->label); ?></b><br>
                    <?php echo esc_html($location->address); ?><br>
                    <?php echo esc_html($location->url); ?><br>
                    <br>Lat: <?php echo esc_html($location->lat); ?>, Lng: <?php echo esc_html($location->lng); ?>
                    <button class="edit-location button button-primary" >Edit</button>
                    <button class="delete-location button button-primary" >Delete</button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}
?>
