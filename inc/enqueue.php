<?php
function enqueue_fake_scrollbar() {
    if (!is_admin()) {
        wp_enqueue_style(
            'fake-scrollbar-css',
            get_template_directory_uri() . '/static/css/fake-scrollbar.css',
            array(),
            '1.0.13'
        );

        wp_enqueue_script(
            'fake-scrollbar-js',
            get_template_directory_uri() . '/static/js/fake-scrollbar.js',
            array(),
            '1.0.13',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_fake_scrollbar');
