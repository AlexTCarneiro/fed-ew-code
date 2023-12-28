<?php
/**
 * Theme Name: A Custom Theme
 * Description: A custom WordPress theme for Evolving Web.
 * Author: Alex C
 * Version: 1.0.1
 */

// Enqueue styles and scripts (Registers styles and scripts if source provided (does NOT overwrite) and enqueues)
function ew_enqueue_styles() {
    wp_enqueue_style('style', get_stylesheet_uri());
    wp_enqueue_script('script', get_template_directory_uri() . '/js/script.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'ew_enqueue_styles');

// Add support for featured images
add_theme_support('post-thumbnails');

// Register custom navigation menus
function ew_register_menus() {
    register_nav_menus(array(
        'primary_menu' => 'Primary Menu',
        'footer_menu' => 'Footer Menu',
    ));
}
add_action('init', 'ew_register_menus');

// Custom post type: Portfolio
function ew_register_portfolio_post_type() {
    register_post_type('portfolio', array(
        'labels' => array(
            'name' => 'Portfolio',
            'singular_name' => 'Portfolio Item',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
    ));
}
add_action('init', 'ew_register_portfolio_post_type');

// Custom widget area
function ew_widgets_init() {
    register_sidebar(array(
        'name' => 'Sidebar',
        'id' => 'sidebar',
        'before_widget' => '<div class="widget">',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ));
}
add_action('widgets_init', 'ew_widgets_init');

// Custom template tags for this theme
require get_template_directory() . '/inc/template-tags.php';

// Custom functions that act independently of the theme templates
require get_template_directory() . '/inc/extras.php';

// Customizer additions
require get_template_directory() . '/inc/customizer.php';

// Load Jetpack compatibility file
require get_template_directory() . '/inc/jetpack.php';
