<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add AJAX actions
add_action('wp_ajax_gml_add_location', 'gml_add_location');
add_action('wp_ajax_gml_delete_location', 'gml_delete_location');
add_action('wp_ajax_gml_update_location', 'gml_update_location');

// Example in gml_add_location function
function gml_add_location() {
    global $wpdb;

    // Ensure input is sanitized and formatted correctly
    $label = sanitize_text_field($_POST['label']);
    $address = sanitize_text_field($_POST['address']);
    $url = esc_url_raw($_POST['url']);
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    $table_name = $wpdb->prefix . 'gml_locations';

    // Log the query for debugging
    ///error_log("Inserting location: label={$label}, lat={$lat}, lng={$lng}");

    $result = $wpdb->insert(
        $table_name,
        [
            'label' => $label,
            'address' => $address,
            'url' => $url,
            'lat' => $lat,
            'lng' => $lng
        ]
    );

    if ($result === false) {
        // Log the error
        error_log("Failed to insert location: " . $wpdb->last_error);
        wp_send_json_error(array('message' => 'Failed to save location: '. $table_name . $wpdb->last_error));
    } else {
        wp_send_json_success();
    }
}


function gml_delete_location() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gml_locations';

    $id = intval($_POST['id']);
    $wpdb->delete($table_name, ['id' => $id]);

    wp_send_json_success();
}

function gml_update_location() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gml_locations';

    $id = intval($_POST['id']);
    $label = sanitize_text_field($_POST['label']);
    $address = sanitize_text_field($_POST['address']);
    $url = esc_url_raw($_POST['url']);
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    $wpdb->update($table_name, [
        'label' => $label,
        'address' => $address,
        'url' => $url,
        'lat' => $lat,
        'lng' => $lng
    ], ['id' => $id]);

    wp_send_json_success();
}
?>
