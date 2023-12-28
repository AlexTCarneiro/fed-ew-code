<?php
/**
 * Theme Name: ReactPress Theme
 * Description: A WordPress theme with React integration for frontend development.
 * Author: Alex C
 * Version: 1.0.1
 */

// Enqueue Editor assets (replace the CDN links with the correct project versions needed)
function reactpress_enqueue_scripts() {
    // Enqueue React and ReactDOM
    wp_enqueue_script('react', 'https://unpkg.com/react@17/umd/react.production.min.js', array(), null, true);
    wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js', array('react'), null, true);

    // Enqueue Babel for JSX transpilation
    wp_enqueue_script('babel', 'https://unpkg.com/@babel/standalone/babel.min.js', array(), null, true);

    // Enqueue custom JavaScript file (if any)
    wp_enqueue_script('custom-scripts', get_template_directory_uri() . '/js/custom-scripts.js', array('react', 'react-dom', 'babel'), null, true);

    // Pass PHP variables to JavaScript
    wp_localize_script('custom-scripts', 'wp_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}

add_action('wp_enqueue_scripts', 'reactpress_enqueue_scripts');

// Create a shortcode for React component
function reactpress_shortcode() {
    ob_start();
    ?>
    <div id="root"></div>

    <script type="text/babel">
        class MyComponent extends React.Component {
            constructor(props) {
                super(props);
                this.state = {
                    message: 'Hello from React!',
                };
            }

            render() {
                return <div>{this.state.message}</div>;
            }
        }

        ReactDOM.render(<MyComponent />, document.getElementById('root'));
    </script>
    <?php

    return ob_get_clean();
}

// React component rendered using shortcode + reactpress
add_shortcode('reactpress', 'reactpress_shortcode');
