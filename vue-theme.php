<?php
/**
 * Theme Name: VuePress Theme
 * Description: A WordPress theme with Vue.js integration for front-end development.
 * Author: Alex C
 * Version: 1.0.1
 */

// Based on Vue WordPress: https://github.com/bshiluk/vue-wordpress/blob/master/README.md
function vuepress_enqueue_scripts() {
    // Enqueue Vue.js
    wp_enqueue_script('vue', 'https://cdn.jsdelivr.net/npm/vue@2.6.14', array(), null, true);

    // Enqueue custom JavaScript file
    wp_enqueue_script('custom-scripts', get_template_directory_uri() . '/js/custom-scripts.js', array('vue'), null, true);

    // Pass PHP variables to JavaScript
    wp_localize_script('custom-scripts', 'wp_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}

add_action('wp_enqueue_scripts', 'vuepress_enqueue_scripts');

// Create a shortcode for Vue.js component
// Customize the Vue component according to the project needs
function vuepress_shortcode() {
    ob_start();
    ?>
    <div id="app">
        <my-component></my-component>
    </div>

    <script>
        new Vue({
            el: '#app',
            components: {
                'my-component': {
                    template: '<div>{{ message }}</div>',
                    data: function () {
                        return {
                            message: 'Hello from Vue.js!',
                        };
                    },
                },
            },
        });
    </script>
    <?php

    return ob_get_clean();
}

// Create a shortcode ([vuepress]) that embeds a simple Vue.js component into a WordPress page or post
add_shortcode('vuepress', 'vuepress_shortcode');
