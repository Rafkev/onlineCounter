<?php
/*
Plugin Name: Online Users Counter
Description: This plugin displays the number of users currently online.
Version: 1.0
Author: RK Oluwasanmi
*/

// Enqueue custom CSS and JavaScript
function online_users_counter_enqueue_scripts() {
    wp_enqueue_style('online-users-counter-custom-css', plugins_url('online-users-counter.css', __FILE__));
    wp_enqueue_script('online-users-counter-custom-js', plugins_url('online-users-counter.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('online-users-counter-custom-js', 'online_users_counter_ajax_object', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'online_users_counter_enqueue_scripts');

// Function to display the number of users online
function display_online_users_count() {
    $users_online = 0;
    $session_time = get_option('online_users_counter_session_time', 900); // Default 15 minutes

    $logged_in_users = get_transient('online_users'); // Retrieve online users from transient

    if (!$logged_in_users) {
        $logged_in_users = array();
    }

    $current_user = wp_get_current_user();

    $session_id = session_id();

    if (!isset($logged_in_users[$current_user->ID]) || $logged_in_users[$current_user->ID] < (time() - $session_time)) {
        $logged_in_users[$current_user->ID] = time();
        set_transient('online_users', $logged_in_users, $session_time); // Set transient with updated online users
    }

    foreach ($logged_in_users as $user_id => $last_active) {
        if ($last_active > (time() - $session_time)) {
            $users_online++;
        } else {
            unset($logged_in_users[$user_id]); // Remove inactive users
        }
    }

    echo '<div class="online-users-counter">';
    echo '<p>Users Online: <span id="online-users-count">' . $users_online . '</span></p>';
    echo '</div>';
}

// Shortcode to display the online users count
function display_online_users_count_shortcode() {
    ob_start();
    display_online_users_count();
    return ob_get_clean();
}
add_shortcode('display_online_users_count', 'display_online_users_count_shortcode');

// AJAX handler to update online users count
function online_users_counter_ajax() {
    $users_online = 0;
    $session_time = get_option('online_users_counter_session_time', 900); // Default 15 minutes

    $logged_in_users = get_transient('online_users'); // Retrieve online users from transient

    if (!$logged_in_users) {
        $logged_in_users = array();
    }

    foreach ($logged_in_users as $user_id => $last_active) {
        if ($last_active > (time() - $session_time)) {
            $users_online++;
        } else {
            unset($logged_in_users[$user_id]); // Remove inactive users
        }
    }

    echo $users_online;

    wp_die();
}
add_action('wp_ajax_online_users_counter_update', 'online_users_counter_ajax');
add_action('wp_ajax_nopriv_online_users_counter_update', 'online_users_counter_ajax');

// Add menu item to settings page
function online_users_counter_menu() {
    add_options_page('Online Users Counter Settings', 'Online Users Counter', 'manage_options', 'online-users-counter-settings', 'online_users_counter_settings_page');
}
add_action('admin_menu', 'online_users_counter_menu');

// Settings page content
function online_users_counter_settings_page() {
    ?>
    <div class="wrap">
        <h2>Online Users Counter Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('online_users_counter_settings_group'); ?>
            <?php do_settings_sections('online-users-counter-settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register settings and fields
function online_users_counter_register_settings() {
    register_setting('online_users_counter_settings_group', 'online_users_counter_session_time', 'intval');
    register_setting('online_users_counter_settings_group', 'online_users_counter_custom_css');
    add_settings_section('online_users_counter_settings_section', 'General Settings', 'online_users_counter_settings_section_callback', 'online-users-counter-settings');
    add_settings_field('online_users_counter_session_time', 'Session Time (seconds)', 'online_users_counter_session_time_callback', 'online-users-counter-settings', 'online_users_counter_settings_section');
    add_settings_field('online_users_counter_custom_css', 'Custom CSS', 'online_users_counter_custom_css_callback', 'online-users-counter-settings', 'online_users_counter_settings_section');
}
add_action('admin_init', 'online_users_counter_register_settings');

// Settings section callback
function online_users_counter_settings_section_callback() {
    echo '<p>Configure general settings for the Online Users Counter plugin.</p>';
}

// Session time field callback
function online_users_counter_session_time_callback() {
    $session_time = get_option('online_users_counter_session_time', 900); // Default 15 minutes
    echo '<input type="number" name="online_users_counter_session_time" value="' . $session_time . '" />';
}

// Custom CSS field callback
function online_users_counter_custom_css_callback() {
    $custom_css = get_option('online_users_counter_custom_css');
    echo '<textarea name="online_users_counter_custom_css" rows="10" cols="50">' . $custom_css . '</textarea>';
}
