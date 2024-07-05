<?php
/*
Plugin Name: Google Maps Locations
Plugin URI:  https://github.com/Ys-sudo/google-maps-locations
Description: A plugin to manage multiple location data for Google Maps.
Version: 1.2
Author: GL
Author URI: https://github.com/Ys-sudo
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
License: GPL v2 or later
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin path
define('GML_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include necessary files
include_once GML_PLUGIN_PATH . 'includes/admin-page.php';
include_once GML_PLUGIN_PATH . 'includes/locations-handler.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'gml_activate_plugin');
register_deactivation_hook(__FILE__, 'gml_deactivate_plugin');

function gml_activate_plugin() {
    gml_drop_locations_table();
    gml_create_locations_table();
}

function gml_deactivate_plugin() {
    // Code to run on deactivation
    gml_drop_locations_table();
}

function gml_create_locations_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gml_locations';
    $charset_collate = $wpdb->get_charset_collate();

    // Log the table name for debugging
    error_log("Creating table: $table_name");

    $sql = "CREATE TABLE $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(255) NOT NULL,
        address VARCHAR(255) NOT NULL,
        url VARCHAR(255),
        lat DOUBLE NOT NULL,
        lng DOUBLE NOT NULL
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Log any errors
    if (!empty($wpdb->last_error)) {
        error_log("Table creation error: " . $wpdb->last_error);
    }
}

function gml_drop_locations_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gml_locations';
    
    // Log the table name for debugging
    error_log("Dropping table: $table_name");
    
    $sql = "DROP TABLE IF EXISTS $table_name;";
    $result = $wpdb->query($sql);

    if ($result === false) {
        // Log the error
        error_log("Failed to drop table: " . $wpdb->last_error);
    }
}

// Enqueue necessary scripts and styles
add_action('admin_enqueue_scripts', 'gml_enqueue_admin_scripts');
function gml_enqueue_admin_scripts() {
    wp_enqueue_script('gml-admin-script', plugins_url('js/admin.js', __FILE__), array('jquery'), null, true);
    wp_enqueue_style('gml-admin-style', plugins_url('css/admin.css', __FILE__));
}



// Register shortcode for displaying map
add_shortcode('gml_map', 'gml_map_shortcode');

function gml_map_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gml_locations'; // Replace 'gml_locations' with your table name

    $query = "SELECT label, address, url, lat, lng FROM $table_name";
    $locations_data = $wpdb->get_results($query, ARRAY_A);

    $labels = wp_list_pluck($locations_data, 'label');
    $addresses = wp_list_pluck($locations_data, 'address');
    $urls = wp_list_pluck($locations_data, 'url');
    $locations = array_map(function($location) {
        return array(
            'lat' => floatval($location['lat']),
            'lng' => floatval($location['lng'])
        );
    }, $locations_data);

    $gml_api_key = get_option('gml_api_key');
    $gml_marker_image = get_option('gml_marker_image');
    $gml_marker_color = get_option('gml_marker_color');
    $gml_marker_border_color = get_option('gml_marker_border_color');
    $gml_marker_scale = get_option('gml_marker_scale');
    $gml_zoom = get_option('gml_zoom');
    $gml_start_lat = get_option('gml_start_lat');
    $gml_start_lng = get_option('gml_start_lng');
    $gml_map_id = get_option('gml_map_id');

    ob_start();
    ?>
    <script type="module">
        import { MarkerClusterer } from "https://cdn.skypack.dev/@googlemaps/markerclusterer@2.3.1";
        var gml_map_data = {
            labels: <?php echo json_encode($labels); ?>,
            addresses: <?php echo json_encode($addresses); ?>,
            urls: <?php echo json_encode($urls); ?>,
            locations: <?php echo json_encode($locations); ?>
        };
        
        var labels = gml_map_data.labels;
        var addresses =  gml_map_data.addresses;
        var urls =  gml_map_data.urls;
        var locations = gml_map_data.locations;

        var markerImage = <?php echo json_encode($gml_marker_image); ?>;
        var markerColor = <?php echo json_encode($gml_marker_color); ?>;
        var markerBorder = <?php echo json_encode($gml_marker_border_color); ?>;
        var markerScale = <?php echo json_encode($gml_marker_scale); ?>;
        var gmlZoom = <?php echo json_encode($gml_zoom); ?>;
        var gmlStartLat = <?php echo json_encode($gml_start_lat); ?>;
        var gmlStartLng = <?php echo json_encode($gml_start_lng); ?>;
        var mapId = <?php echo json_encode($gml_map_id); ?>;
        
        async function initMap() {
        // Request needed libraries.
        const { Map, InfoWindow } = await google.maps.importLibrary("maps");
        const { AdvancedMarkerElement, PinElement } = await google.maps.importLibrary(
        "marker",
        );

        const map = new google.maps.Map(document.getElementById("gml-map"), {
            zoom: Number(gmlZoom) || 2,
            center: {
            lat: Number(gmlStartLat) || 52.0425712,
            lng: Number(gmlStartLng) || 18.9673297
            },
            mapId: mapId || null,
        });
        const infoWindow = new google.maps.InfoWindow({
        content: "",
        disableAutoPan: true,
        });


        // Add some markers to the map.
        const markers = locations.map((position, i) => {

            const label = `<div style="text-align:center">
                                <b>${labels[i]}</b>
                                <p>${addresses[i]}</p>
                                ${urls[i] ? `<a href="${urls[i]}" class="gml_web_link" target="_blank" rel="noopener noreferrer">
                                    <svg width="24" height="24" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                        <path d="M15.6,7.2H14v1.5h1.6c2,0,3.7,1.7,3.7,3.7s-1.7,3.7-3.7,3.7H14v1.5h1.6c2.8,0,5.2-2.3,5.2-5.2,0-2.9-2.3-5.2-5.2-5.2zM4.7,12.4c0-2,1.7-3.7,3.7-3.7H10V7.2H8.4c-2.9,0-5.2,2.3-5.2,5.2,0,2.9,2.3,5.2,5.2,5.2H10v-1.5H8.4c-2,0-3.7-1.7-3.7-3.7zm4.6.9h5.3v-1.5H9.3v1.5z"></path>
                                    </svg>
                                </a>` : ''}
                                <br/>
                            </div>`;

            const markerImageURL = markerImage ? new URL(markerImage) : null;
            const pinGlyph = markerImageURL ? new google.maps.marker.PinElement({
                glyph: markerImageURL,
                background: markerColor || '#eb2c2c',
                borderColor: markerBorder || '#840505',
                scale: Number(markerScale) || .9,
            }) : new google.maps.marker.PinElement({
                glyph: null,
                background: markerColor || '#eb2c2c',
                borderColor: markerBorder || '#840505',
                scale: Number(markerScale) || .9,
            });
        const marker = new google.maps.marker.AdvancedMarkerElement({
        position,
        content: pinGlyph.element,
        });
        // markers can only be keyboard focusable when they have click listeners
        // open info window when marker is clicked
        marker.addListener("click", () => {
        infoWindow.setContent(label);
        infoWindow.open(map, marker);
        });
        return marker;
        });



        // Add a marker clusterer to manage the markers.
        new MarkerClusterer({ markers, map });
        }


        initMap();
    </script>
    <div id="gml-map" class="card" style="width: 100%;height: 500px;min-width:100%;min-height:500px;position:relative;"></div>
    
    <script>
    (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})
        ({key: <?php echo json_encode($gml_api_key); ?>, v: "weekly"});</script>
    <?php
    return ob_get_clean();
}



?>
