<?php
/**
 * Custom Post Types Registration
 */
add_action('init', function () {
    register_post_type('mylog_user', array(
        'labels' => array('name' => 'MyLog Users', 'singular_name' => 'MyLog User'),
        'public' => true, 
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-universal-access',
        'supports' => array('title', 'custom-fields'),
    ));

    register_post_type('mylog_entry', array(
        'labels' => array('name' => 'MyLog Entries', 'singular_name' => 'MyLog Entry'),
        'public' => true, 
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-book',
        'supports' => array('title', 'author', 'thumbnail', 'custom-fields'),
    ));
});