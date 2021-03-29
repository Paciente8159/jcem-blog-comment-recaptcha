<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/Paciente8159
 * @since             1.0.0
 * @package           JCEM_Blog_Comment_Recaptcha_v3
 *
 * @wordpress-plugin
 * Plugin Name:       JCEM Blog Comment Recaptcha v3
 * Plugin URI:        https://github.com/Paciente8159
 * Description:       This is plugin to integrate Google Recaptcha v3 on blog comment forms
 * Version:           1.0.0
 * Author:            Joao Martins
 * Author URI:        https://github.com/Paciente8159
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       jcem_blog_comment_recaptcha
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die;
}

define('RECAPTCHA_SITE_KEY', 'YOUR_SITE_KEY');
define('RECAPTCHA_SECRET_KEY', 'YOUR_SECRET_KEY');

add_action('wp_head', function () {
    if (get_post_type() == 'post') {
?>
        <script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_SITE_KEY; ?>" async></script>
    <?php
    }
});

add_filter('comment_form_submit_button', function ($submit_button, $args) {
    $new_button = sprintf(
        '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" onclick="recaptchaSubmit(this,event)"/>',
        esc_attr($args['name_submit']),
        esc_attr($args['id_submit']),
        esc_attr($args['class_submit']),
        esc_attr($args['label_submit'])
    );
    return $new_button;
}, 10, 2);

add_filter('comment_form_submit_field', function ($submit_field, $args) {
    $submit_id = $args['id_submit'];
    $submit_form = $args['id_form'];
    $submit_field .= sprintf('<input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" data-submit="%s" data-form="%s" value="">', $submit_id, $submit_form);

    return $submit_field;
}, 10, 2);


add_filter('pre_comment_approved', function ($approved, $commentdata) {
    $api = 'https://www.google.com/recaptcha/api/siteverify';
    $secret = RECAPTCHA_SECRET_KEY;
    $response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
    $query = sprintf('%s?secret=%s&response=%s&remoteip=%s', $api, $secret, $response, $_SERVER['REMOTE_ADDR']);
    $validation = file_get_contents($query);
    $valid = json_decode($validation, false);

    if ($valid->success == true && $valid->score <= 0.5) {
        return 'spam';
    }
    
    return $approved;
}, 10, 2);

add_action('wp_footer', function () {
    ?>
    <script type="text/javascript" async>
        function recaptchaSubmit(b, e) {
            e.preventDefault();
            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo RECAPTCHA_SITE_KEY; ?>', {
                    action: 'submit'
                }).then(function(token) {
                    var r = document.getElementById("g-recaptcha-response");
                    r.value = token;
                    if (b.id == 'submit') {
                        b.removeAttribute("name");
                        b.id = "recaptcha-" + b.id;
                    }
                    var f = document.getElementById(r.getAttribute('data-form'));
                    f.submit();
                });
            });
        }
    </script>
<?php
});
