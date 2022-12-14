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
            add_filter( 'dt_blank_title', [ $this, 'page_tab_title' ] ); // adds basic title to browser tab
            add_action( 'wp_print_scripts', [ $this, 'print_scripts' ], 1500 ); // authorizes scripts
            add_action( 'wp_print_styles', [ $this, 'print_styles' ], 1500 ); // authorizes styles

            // page content
            add_action( 'dt_blank_head', [ $this, '_header' ] );
            add_action( 'dt_blank_footer', [ $this, '_footer' ] );
            add_action( 'dt_blank_body', [ $this, 'body' ] );

            add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
            add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );

        }
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        return $allowed_css;
    }

    public function header_style(){
        ?>
        <style>
            body {
                background-color:white;
                padding: 1em;
            }
            #signon {
                width: 100%;
            }
            #login-logo {
                width: 100px;
                position:absolute;
                top:20px;
                left:20px;
                display:none;
            }
        </style>
        <?php
    }

    public function body(){
        ?>
        <script>
            const d = new Date();
            window.time = d.getTime();
            let params = {
                "title": "Test Contact " + window.time
            }
            let queryParams = JSON.stringify(params) //jQuery.param( params )
        </script>
        <div id="signon">
            <div id="login-logo">
                <img src="<?php echo trailingslashit( plugin_dir_url(__FILE__) ) ?>mysteryman.png" width="30px;" /><br>
                <span id="u_name"></span>
            </div>
        </div>
        <div style="width:56%; float:left; text-align: center;">
            <button class="button" onclick="window.register_user()">Register User</button><br>
            <div style="text-align:center; margin: 0 auto; width:200px;"><input type="text" id="username" placeholder="Username" value="" /> </div>
            <div style="text-align:center; margin: 0 auto; width:200px;"><input type="password" id="password" placeholder="Password" value="" /> </div>
            <button class="button" id="goodLogin">Login</button><br>
            <button class="button" id="logout">Logout</button><br>
            <hr>
            <button class="button" onclick="window.api_remote_get('dt/v1/user/my').done(function(data){console.log(data); jQuery('#response').html(JSON.stringify(data))})">Remote Me</button><br>
            <button class="button" id="test">Test Loggedin</button><br>
            <hr>
            <button class="button" onclick="window.api_remote_post('dt-posts/v2/contacts/', params).done(function(data){console.log(data);jQuery('#response').html(JSON.stringify(data))})">Create Contact</button><br>
            <div style="text-align:center; margin: 0 auto; width:100px;"><input type="text" id="contact_id" placeholder="Post Id" value="" /> </div><button class="button" onclick="window.get_contact()">Get Contact</button><br>
            <hr>
            <button class="button" onclick="window.api_remote_post('dt-posts/v2/trainings/', params).done(function(data){console.log(data);jQuery('#response').html(JSON.stringify(data))})">Create Training</button><br>
            <div style="text-align:center; margin: 0 auto; width:100px;"><input type="text" id="training_id" placeholder="Post Id" value="" /> </div><button class="button" onclick="window.get_training()">Get Training</button><br>
            <hr>
        </div>
        <div id="response" style="width: 22%; float: left;"></div>

        <script>
            jQuery(document).ready(function(){

                window.jsObject = [<?php echo json_encode([
                    'root' => esc_url_raw( rest_url() ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                    'translations' => [
                        'add' => __( 'Add Magic', 'prayer-global' ),
                    ],
                ]) ?>][0]

                window.user_object = false

                if ( typeof localStorage.token !== 'undefined' ) {
                    jQuery('#login-logo').show()
                    jQuery('#u_name').html(localStorage.user_display_name)
                }

                window.api_post = ( action, data ) => {
                    return jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify({ action: action, data: data }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: window.jsObject.root + 'dt-login/v1/login',
                        beforeSend: function (xhr) {
                            if (localStorage.token) {
                                xhr.setRequestHeader('Authorization', 'Bearer ' + localStorage.token);
                            }
                        }
                    })
                        .fail(function(e) {
                            console.log(e)
                        })
                }
                window.api_remote_post = ( endpoint, data ) => {
                    return jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(data),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: `https://zume5.training/tools/wp-json/`+endpoint,
                        beforeSend: function (xhr) {
                            if (localStorage.token) {
                                xhr.setRequestHeader('Authorization', 'Bearer ' + localStorage.token);
                            }
                        }
                    })
                        .fail(function(e) {
                            console.log(e)
                        })
                }
                window.api_remote_get = ( endpoint ) => {
                    return jQuery.ajax({
                        type: "GET",
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: `https://zume5.training/tools/wp-json/`+endpoint,
                        beforeSend: function (xhr) {
                            if (localStorage.token) {
                                xhr.setRequestHeader('Authorization', 'Bearer ' + localStorage.token);
                            }
                        }
                    })
                        .fail(function(e) {
                            console.log(e)
                        })
                }

                window.get_contact = () => {
                    let pid = jQuery('#contact_id').val()
                    window.api_remote_post('dt-posts/v2/contacts/'+pid)
                        .done(function(data){
                            console.log(data)
                            jQuery('#response').html(JSON.stringify(data))
                        })
                }
                window.get_training = () => {
                    let pid = jQuery('#training_id').val()
                    window.api_remote_post('dt-posts/v2/trainings/'+pid)
                        .done(function(data){
                            console.log(data)
                            jQuery('#response').html(JSON.stringify(data))
                        })
                }


                window.register_user = () => {
                    let new_user_params = {
                        'user-email': window.time + '@email.com',
                        'user-display': window.time,
                        'user-password': window.time,
                        'locale': 'en'
                    }
                    window.api_remote_post('dt/v1/users/register', new_user_params)
                        .done(function(data){
                            console.log(data)
                            window.user_object = data
                            localStorage.token = data.jwt.token;
                            localStorage.user_display_name = data.jwt.user_display_name
                            jQuery('#login-logo').show()
                            jQuery('#u_name').html(localStorage.user_display_name)
                            jQuery('#response').html(JSON.stringify(data))
                        })
                }

                jQuery('#test').click(function() {
                    jQuery.ajax({
                        type: 'POST',
                        url: 'https://zume5.training/tools/wp-json/jwt-auth/v1/token/validate',
                        beforeSend: function(xhr) {
                            if (localStorage.token) {
                                xhr.setRequestHeader('Authorization', 'Bearer ' + localStorage.token);
                            }
                        },
                        success: function(data) {
                            if ( data ) {
                                jQuery('#login-logo').show()
                                jQuery('#response').html(JSON.stringify(data))
                                alert('Hello ' + localStorage.user_display_name + '!');
                            } else {
                                jQuery('#login-logo').hide()
                                jQuery('#response').html(JSON.stringify(data))
                                alert("Sorry, you are not logged in.");
                            }
                        },
                        error: function() {
                            alert("Sorry, you are not logged in.");
                        }
                    });
                });
                jQuery('#goodLogin').click(function() {
                    let username = jQuery('#username').val()
                    let password = jQuery('#password').val()
                    jQuery.ajax({
                        type: "POST",
                        url: "https://zume5.training/tools/wp-json/jwt-auth/v1/token",
                        data: {
                            username: username,
                            password: password
                        },
                        success: function(data) {
                            console.log(data)
                            localStorage.token = data.token;
                            localStorage.user_display_name = data.user_display_name
                            jQuery('#login-logo').show()
                            jQuery('#u_name').html(localStorage.user_display_name)
                            jQuery('#response').html(JSON.stringify(data))
                        },
                        error: function() {
                            alert("Login Failed");
                        }
                    });
                });

                jQuery('#logout').click(function() {
                    jQuery('#login-logo').hide()
                    localStorage.clear();
                });
            })
        </script>
        <?php
    }
}
Zume_App_Porch_Magic_Home_App::instance();
