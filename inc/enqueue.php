<?php
/**
 * Enqueue Scripts and Styles
 */

add_action('wp_enqueue_scripts', function() {

    wp_enqueue_style(
        'mymate-app-style',
        get_stylesheet_directory_uri() . '/style.css',
        array(),
        '1.0.7',
        'all'
    );

    wp_enqueue_script(
        'mylog-enhancements',
        get_stylesheet_directory_uri() . '/js/mylog-enhancements.js',
        array('jquery'),
        '1.0.1',
        true
    );

    wp_localize_script('mylog-enhancements', 'mylog_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('mylog_nonce'),
    ));

}, 100);

// Mobile meta tags
add_action('wp_head', function() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
    echo '<meta name="theme-color" content="#3b82f6">' . "\n";
}, 1);