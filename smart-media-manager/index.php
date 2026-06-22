<?php
/*
Plugin Name: Smart Media Manager
Plugin URI: https://aminarjmand.com
Description: مدیریت هوشمند رسانه (اندازه، کیفیت و گالری تصاویر حرفه‌ای)
Version: 1.8
Author: امین ارجمند
Author URI: https://aminarjmand.com
Text Domain: smart-media-manager
Domain Path: /languages
Requires at least: 5.0
Requires PHP: 7.4
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ========== تنظیمات کلیدهای option ==========
 */
define( 'CFM_OPTION_ENABLE_ISQM', 'cfm_enable_isqm' );
define( 'CFM_OPTION_ENABLE_FANCY', 'cfm_enable_fancybox' );
define( 'CFM_OPTION_ENABLE_FALLBACK', 'cfm_enable_fallback_full' );

define( 'CFM_OPTION_ISQM_JPEG', 'cfm_isqm_jpeg_quality' );
define( 'CFM_OPTION_ISQM_PNG',  'cfm_isqm_png_quality' );
define( 'CFM_OPTION_DISABLED_SIZES', 'cfm_disabled_image_sizes' );
define( 'CFM_OPTION_DISABLE_GUTEN', 'cfm_disable_gutenberg' );

/**
 * بارگذاری متن‌دامین
 */
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'smart-media-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
});

/**
 * منوی تنظیمات
 */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'upload.php',
        __( 'Smart Media Manager', 'smart-media-manager' ),
        __( 'Smart Media Manager', 'smart-media-manager' ),
        'manage_options',
        'cfm_settings_page',
        'cfm_display_settings_page'
    );
});

/**
 * نمایش صفحهٔ تنظیمات
 */
function cfm_display_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( isset( $_POST['cfm_settings_submit'] ) ) {
        check_admin_referer( 'cfm_settings_nonce', 'cfm_settings_nonce_field' );

        update_option( CFM_OPTION_ENABLE_ISQM, isset( $_POST['enable_isqm'] ) ? 1 : 0 );
        update_option( CFM_OPTION_ENABLE_FANCY, isset( $_POST['enable_fancybox'] ) ? 1 : 0 );
        update_option( CFM_OPTION_ENABLE_FALLBACK, isset( $_POST['enable_fallback'] ) ? 1 : 0 );

        if ( isset( $_POST['enable_isqm'] ) ) {
            $disabled_sizes = isset( $_POST['disabled_image_sizes'] ) ? array_map( 'sanitize_text_field', (array) $_POST['disabled_image_sizes'] ) : array();
            update_option( CFM_OPTION_DISABLED_SIZES, $disabled_sizes );
            update_option( CFM_OPTION_ISQM_JPEG, isset( $_POST['cfm_jpeg_100'] ) ? 100 : 90 );
            update_option( CFM_OPTION_ISQM_PNG,  isset( $_POST['cfm_png_100'] ) ? 100 : 90 );
            
            $disable_guten = isset( $_POST['cfm_disable_gutenberg'] ) ? 1 : 0;
            update_option( CFM_OPTION_DISABLE_GUTEN, $disable_guten );
            update_option( 'classic-editor-replace', $disable_guten ? 'classic' : false );
        }
        echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
    }

    $enable_isqm     = get_option( CFM_OPTION_ENABLE_ISQM, 0 );
    $enable_fancy    = get_option( CFM_OPTION_ENABLE_FANCY, 0 );
    $enable_fallback = get_option( CFM_OPTION_ENABLE_FALLBACK, 0 );
    $image_sizes     = cfm_get_all_image_sizes();
    $disabled_sizes  = get_option( CFM_OPTION_DISABLED_SIZES, array() );
    $jpeg_quality    = get_option( CFM_OPTION_ISQM_JPEG, 90 );
    $png_quality     = get_option( CFM_OPTION_ISQM_PNG, 90 );
    $disable_guten   = get_option( CFM_OPTION_DISABLE_GUTEN, 0 );
    ?>
    <div class="wrap">
        <h1>مدیریت هوشمند رسانه</h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'cfm_settings_nonce', 'cfm_settings_nonce_field' ); ?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="postbox">
                            <h2 class="hndle"><span>ماژول‌های فعال</span></h2>
                            <div class="inside">
                                <p><label><input type="checkbox" name="enable_isqm" value="1" <?php checked( $enable_isqm, 1 ); ?> /> <strong>فعالسازی مدیریت اندازه و کیفیت تصاویر</strong></label></p>
                                <p><label><input type="checkbox" name="enable_fancybox" value="1" <?php checked( $enable_fancy, 1 ); ?> /> <strong>فعالسازی گالری پیشرفته Fancybox</strong></label></p>
                                <hr />
                                <p>
                                    <label><input type="checkbox" name="enable_fallback" value="1" <?php checked( $enable_fallback, 1 ); ?> /> <strong>جایگزینی خودکار سایز اصلی (Fallback)</strong></label>
                                    <br><small>اگر سایز درخواستی در هاست موجود نباشد، تصویر اصلی نمایش داده می‌شود.</small>
                                </p>
                            </div>
                        </div>

                        <div class="postbox" id="cfm-isqm-settings" style="<?php echo $enable_isqm ? '' : 'display:none;'; ?>">
                            <h2 class="hndle"><span>تنظیمات تصاویر و ویرایشگر</span></h2>
                            <div class="inside">
                                <h4>غیرفعال‌سازی اندازه‌های خودکار:</h4>
                                <ul style="columns: 2; background: #fdfdfd; padding: 15px; border: 1px solid #eee;">
                                    <?php foreach ( $image_sizes as $name => $meta ) : ?>
                                        <li><label><input type="checkbox" name="disabled_image_sizes[]" value="<?php echo esc_attr($name); ?>" <?php checked(in_array($name, (array)$disabled_sizes)); ?> /> <?php echo esc_html($name); ?> <small>(<?php echo $meta['width'].'x'.$meta['height']; ?>)</small></label></li>
                                    <?php endforeach; ?>
                                </ul>
                                <hr />
                                <p><label><input type="checkbox" name="cfm_jpeg_100" value="1" <?php checked($jpeg_quality == 100); ?> /> کیفیت JPEG روی 100%</label></p>
                                <p><label><input type="checkbox" name="cfm_png_100" value="1" <?php checked($png_quality == 100); ?> /> کیفیت PNG روی 100%</label></p>
                                <hr />
                                <p><label><input type="checkbox" name="cfm_disable_gutenberg" value="1" <?php checked($disable_guten, 1); ?> /> <strong>غیرفعال کردن گوتنبرگ و استفاده از ویرایشگر کلاسیک</strong></label></p>
                            </div>
                        </div>
                        <p class="submit"><input type="submit" name="cfm_settings_submit" class="button button-primary button-large" value="ذخیره تغییرات نهایی" /></p>
                    </div>
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="postbox">
                            <h2 class="hndle"><span>راهنمای سریع</span></h2>
                            <div class="inside">
                                <p><strong>Fancybox:</strong> تمام گالری‌های وردپرس و المنتور خودکار شناسایی و به صورت اسلایدر حرفه‌ای نمایش داده می‌شوند.</p>
                                <hr><p><strong>Fallback:</strong> از نمایش تصاویر شکسته در سایت جلوگیری می‌کند.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var isqmBox = document.querySelector('input[name="enable_isqm"]');
        var isqmSection = document.getElementById('cfm-isqm-settings');
        if(isqmBox && isqmSection) {
            isqmBox.addEventListener('change', function() { isqmSection.style.display = this.checked ? 'block' : 'none'; });
        }
    });
    </script>
    <?php
}

function cfm_get_all_image_sizes() {
    global $_wp_additional_image_sizes;
    $sizes = array();
    foreach ( get_intermediate_image_sizes() as $s ) {
        $sizes[$s]['width']  = get_option( "{$s}_size_w" );
        $sizes[$s]['height'] = get_option( "{$s}_size_h" );
    }
    if ( isset( $_wp_additional_image_sizes ) ) {
        foreach ( $_wp_additional_image_sizes as $s => $meta ) {
            $sizes[$s]['width']  = $meta['width'];
            $sizes[$s]['height'] = $meta['height'];
        }
    }
    return $sizes;
}

/**
 * اعمال محدودیت روی سایزها
 */
add_filter( 'intermediate_image_sizes_advanced', function( $sizes ) {
    if ( ! get_option( CFM_OPTION_ENABLE_ISQM ) ) return $sizes;
    $disabled = get_option( CFM_OPTION_DISABLED_SIZES, array() );
    foreach ( $disabled as $s ) { unset( $sizes[$s] ); }
    return $sizes;
});

add_filter( 'jpeg_quality', function() { return get_option( CFM_OPTION_ENABLE_ISQM ) ? get_option( CFM_OPTION_ISQM_JPEG, 90 ) : 90; });
add_filter( 'wp_editor_set_quality', function() { return get_option( CFM_OPTION_ENABLE_ISQM ) ? get_option( CFM_OPTION_ISQM_PNG, 90 ) : 90; });

add_filter( 'use_block_editor_for_post', function( $use ) {
    return ( get_option( CFM_OPTION_ENABLE_ISQM ) && get_option( CFM_OPTION_DISABLE_GUTEN ) ) ? false : $use;
}, 10);
add_filter( 'use_widgets_block_editor', function( $use ) {
    return ( get_option( CFM_OPTION_ENABLE_ISQM ) && get_option( CFM_OPTION_DISABLE_GUTEN ) ) ? false : $use;
});

/**
 * FALLBACK - جلوگیری از خطای ویرایشگر
 */
add_filter( 'image_downsize', function( $return, $attachment_id, $size ) {
    // اگر در ادمین هستیم، افزونه غیرفعال است، خروجی از قبل آماده است، یا سایز درخواستی full است، کاری انجام نده.
    if ( is_admin() || ! get_option( CFM_OPTION_ENABLE_FALLBACK ) || $return || $size === 'full' ) return $return;

    // رفع مشکل Illegal offset type: بررسی می‌کنیم که $size حتماً یک رشته (متن) باشد.
    if ( ! is_string( $size ) ) {
        return $return;
    }

    $meta = wp_get_attachment_metadata( $attachment_id );
    
    // اطمینان حاصل می‌کنیم که $meta آرایه است و سایز درخواستی درون آن وجود ندارد
    if ( ! is_array( $meta ) || empty( $meta['sizes'] ) || ! isset( $meta['sizes'][$size] ) ) {
        
        // به جای wp_get_attachment_image_src مستقیم آدرس تصویر اصلی را می‌گیریم تا حلقه بی‌نهایت ایجاد نشود
        $img_url = wp_get_attachment_url( $attachment_id );
        if ( $img_url ) {
            $width  = isset( $meta['width'] ) ? $meta['width'] : 0;
            $height = isset( $meta['height'] ) ? $meta['height'] : 0;
            
            // برگرداندن ساختار استاندارد: [آدرس, عرض, طول, آیا سایز سفارشی است]
            return array( $img_url, $width, $height, false );
        }
    }
    
    return $return;
}, 10, 3);

/**
 * FANCYBOX - دقیقاً مشابه عکس ارسالی
 */
add_action( 'wp_enqueue_scripts', function() {
    if ( ! get_option( CFM_OPTION_ENABLE_FANCY ) ) return;
    
    wp_enqueue_style( 'cfm-fancybox-css', plugin_dir_url( __FILE__ ) . 'fancybox/fancybox.css' );
    wp_enqueue_script( 'cfm-fancybox-js', plugin_dir_url( __FILE__ ) . 'fancybox/fancybox.umd.js', array('jquery'), null, true );
    
    $inline_js = "
    jQuery(document).ready(function($){
        // پیدا کردن تمام لینک‌های تصویر در گالری‌ها و کل متن
        function setupFancybox() {
            var galleryGroups = 0;
            $('.gallery, .wp-block-gallery, .elementor-image-gallery').each(function(){
                galleryGroups++;
                var groupName = 'gallery-group-' + galleryGroups;
                $(this).find('a').each(function(){
                    var href = $(this).attr('href');
                    if (href && href.match(/\.(jpg|jpeg|png|webp|avif|gif|bmp)($|\?)/i)) {
                        $(this).attr('data-fancybox', groupName);
                    }
                });
            });

            // برای تصاویر تکی خارج از گالری
            $('a').each(function(){
                var href = $(this).attr('href');
                if (href && href.match(/\.(jpg|jpeg|png|webp|avif|gif|bmp)($|\?)/i) && !$(this).attr('data-fancybox')) {
                    $(this).attr('data-fancybox', 'single-images');
                }
            });

            if (typeof Fancybox !== 'undefined') {
                Fancybox.bind('[data-fancybox]', {
                    infinite: true,
                    keyboard: true,
                    rtl: true,
                    Toolbar: {
                        display: {
                            left: ['infobar'],
                            middle: [],
                            right: ['zoom', 'slideshow', 'fullscreen', 'download', 'thumbs', 'close'],
                        }
                    },
                    Thumbs: {
                        autoStart: true, // نمایش خودکار بندانگشتی‌ها مشابه عکس
                        type: 'classic'
                    },
                    Html: {
                        videoAutoplay: true
                    },
                    l10n: {
                        CLOSE: 'بستن',
                        NEXT: 'بعدی',
                        PREV: 'قبلی',
                        MODAL: 'این پنجره را می‌توان با کلید ESC بست',
                        ERROR: 'خطایی رخ داده است، لطفاً بعداً دوباره تلاش کنید',
                        IMAGE_ERROR: 'تصویر یافت نشد',
                        DOWNLOAD: 'دانلود',
                        FULLSCREEN: 'تمام صفحه',
                        THUMBS: 'بندانگشتی‌ها',
                        ZOOM: 'بزرگنمایی'
                    }
                });
            }
        }
        setupFancybox();
    });";
    wp_add_inline_script( 'cfm-fancybox-js', $inline_js );
});

add_filter( 'plugin_row_meta', function( $links, $file ) {
    if ( strpos( $file, plugin_basename(__FILE__) ) !== false ) {
        $links[] = '<a href="https://aminarjmand.com" target="_blank">سایت سازنده</a>';
    }
    return $links;
}, 10, 2 );