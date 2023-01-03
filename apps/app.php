<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Adds a magic link home page to the Disciple Tools system.
 * @usage This could be used to add a microsite in front of the Disciple Tools system. Or used to hide the
 * Disciple Tools login behind a false store front. Or used to extend an entire application to the public out
 * in front of the Disciple Tools system.
 *
 * @example https://yoursite.com/(empty)
 *
 * @see https://disciple.tools/plugins/porch/
 * @see https://disciple.tools/plugins/disciple-tools-porch-template/
 */
class Zume_App_Porch_Magic_Home_App extends DT_Magic_Url_Base
{
    public $magic = false;
    public $parts = false;
    public $page_title = 'Zúme App';
    public $root = 'zume_app';
    public $type = 'home';
    public static $token = 'zume_app_home';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        parent::__construct();

        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );

        $url = dt_get_url_path();
        if ( empty( $url ) && ! dt_is_rest() ) { // this filter is looking for the root site url without params.

            // register url and access
            add_action( 'template_redirect', [ $this, 'theme_redirect' ] );
            add_filter( 'dt_blank_access', function (){ return true;
            }, 100, 1 ); // allows non-logged in visit
            add_filter( 'dt_allow_non_login_access', function (){ return true;
            }, 100, 1 );
            add_filter( 'dt_override_header_meta', function (){ return true;
            }, 100, 1 );

            // header content
            add_filter( 'dt_blank_title', [ $this, 'page_tab_title' ] );
            add_action( 'wp_print_scripts', [ $this, 'print_scripts' ], 1500 );
            add_action( 'wp_print_styles', [ $this, 'print_styles' ], 1500 );

            // page content
            add_action( 'dt_blank_head', [ $this, '_header' ] );
            add_action( 'dt_blank_footer', [ $this, '_footer' ] );
            add_action( 'dt_blank_body', [ $this, 'body' ] );

            add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
            add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );

            add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 999 );
        }
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'jquery-touch-punch';
        $allowed_js[] = 'lodash';
        $allowed_js[] = 'app-js';
        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css[] = 'app-css';
        return $allowed_css;
    }

    public function scripts() {
        wp_register_script( 'jquery-touch-punch', '/wp-includes/js/jquery/jquery.ui.touch-punch.js' ); // @phpcs:ignore
        wp_enqueue_script('app-js', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'app.base.js', [
            'jquery',
        ], filemtime( plugin_dir_path( __FILE__ ) .'app.base.js' ), true );

        wp_enqueue_style( 'app-css', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'app.css', [ 'site-css' ], filemtime( plugin_dir_path( __FILE__ ) .'app.css' ) );
    }

    public function header_style(){
        ?>
        <style>
            body {
                background-color: white;
            }
        </style>
        <?php
    }

    public function body(){
        ?>
        <div id="custom-style"></div>
        <div id="spinner-background" class="loading-spinner"></div>

        <!-- title -->
        <div data-sticky-container>
            <div class="title-bar" data-sticky data-options="marginTop:0;" style="width:100%">
                <div class="title-bar-left" >
                </div>

                <div class="title-bar-right">
                    <div style="float:right;width:40px;font-size: 20px;padding: 0 1em;"><button class="menu-icon" type="button" data-open="offCanvasLeft"></button></div>
                    <div style="float:right;width:40px;padding: 0 1em;"><?php do_shortcode('[zume_logon_button]'); ?></div>
                </div>
            </div>
        </div>

        <!-- off canvas menus -->
        <div class="off-canvas-wrapper">
            <!-- Left Canvas -->
            <div class="off-canvas position-right" id="offCanvasLeft" data-off-canvas data-transition="push">
                <button class="close-button" aria-label="Close alert" type="button" data-close>
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="grid-x grid-padding-x">
                    <div class="cell center" style="padding-top: 1em;">
                        <h2>Zume</h2>
                        <?php do_shortcode('[zume_logon_button_with_name]'); ?>
                    </div>
                    <div class="cell"><hr></div>
                    <div class="cell"></div>
                    <div class="cell"><hr></div>
                </div>
            </div>
        </div>

        <!-- body-->
        <div id="wrapper">
            <div class="grid-x" id="content"><div class="cell center" style="padding:1em;"><span class="loading-spinner active"></span></div></div>
            <div style="height:200px"><!--bottom list spacer--></div>
        </div>
        <?php do_shortcode('[zume_footer_logon_modal]'); ?>
        <?php
    }

    public function add_endpoints() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->type, [
                [
                    'methods'  => "POST",
                    'callback' => [ $this, 'endpoint' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    public function endpoint( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );
        $action = sanitize_text_field( wp_unslash( $params['action'] ) );

        switch ( $action ) {
            case 'get_all':
                return $params;
            default:
                return new WP_Error( __METHOD__, "Missing valid action", [ 'status' => 400 ] );
        }
    }
}
Zume_App_Porch_Magic_Home_App::instance();
