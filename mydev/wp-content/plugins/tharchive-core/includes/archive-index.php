<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 渲染接力活动索引 Shortcode
 */
function tharchive_render_relay_index_shortcode() {
    // 引入前端脚本和样式
    wp_enqueue_style(
        'tharchive-archive-app-style',
        THARCHIVE_CORE_URL . 'assets/dist/archive-app.css',
        array(),
        '0.1.0'
    );

    wp_enqueue_script(
        'tharchive-archive-app-script',
        THARCHIVE_CORE_URL . 'assets/dist/archive-app.js',
        array(), // 不依赖 jQuery，纯 Vue
        '0.1.0',
        true
    );

    // 将 API 路径注入到前端
    wp_localize_script(
        'tharchive-archive-app-script',
        'tharchiveApi',
        array(
            'restUrl' => esc_url_raw( rest_url() ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        )
    );

    // 输出挂载点
    ob_start();
    ?>
    <div id="tharchive-relay-index"></div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tharchive_relay_index', 'tharchive_render_relay_index_shortcode' );
