<?php
/**
 * This template provides the setting view of the plugin
 *
 *
 * @link       https://codeboxr.com
 * @since      1.0.0
 *
 * @package    cbxuseronline
 * @subpackage cbxuseronline/templates
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<?php
$save_svg = cbxuseronline_load_svg( 'icon_save' );
?>

<div class="wrap cbx-chota cbxuseronline-page-wrapper cbxuseronline-setting-wrapper" id="cbxuseronline-setting">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2></h2>
				<?php
				settings_errors();
				?>
				<?php do_action( 'cbxuseronline_wpheading_wrap_before', 'settings' ); ?>
                <div class="wp-heading-wrap">
                    <div class="wp-heading-wrap-left pull-left">
						<?php do_action( 'cbxuseronline_wpheading_wrap_left_before', 'settings' ); ?>
                        <h1 class="wp-heading-inline wp-heading-inline-cbxuseronline">
							<?php esc_html_e( 'Useronline: Global Settings', 'cbxuseronline' ); ?>
                        </h1>
						<?php do_action( 'cbxuseronline_wpheading_wrap_left_after', 'settings' ); ?>
                    </div>
                    <div class="wp-heading-wrap-right  pull-right">
						<?php do_action( 'cbxuseronline_wpheading_wrap_right_before', 'settings' ); ?>
                        <a href="<?php echo esc_url(admin_url( 'admin.php?page=cbxuseronline-support' )); ?>" class="button outline primary"><?php esc_html_e( 'Support & Docs', 'cbxuseronline' ); ?></a>
                        <a href="#" id="save_settings" class="button primary icon icon-right  mr-5"><?php esc_html_e( 'Save Settings', 'cbxuseronline' ); ?>
                            <i class="cbx-icon">
		                        <?php
		                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		                        echo $save_svg;
		                        ?>
                            </i>
                        </a>
						<?php do_action( 'cbxuseronline_wpheading_wrap_right_after', 'settings' ); ?>
                    </div>
                </div>
				<?php do_action( 'cbxuseronline_wpheading_wrap_after', 'settings' ); ?>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-12">
				<?php do_action( 'cbxuseronline_settings_form_before', 'settings' ); ?>
                <div class="postbox">
                    <div class="clear clearfix"></div>
                    <div class="inside setting-form-wrap">
                        <div class="clear clearfix"></div>
						<?php do_action( 'cbxuseronline_settings_form_start', 'settings' ); ?>
						<?php
						$settings->show_navigation();
						$settings->show_forms();
						?>
						<?php do_action( 'cbxuseronline_settings_form_end', 'settings' ); ?>
                        <div class="clear clearfix"></div>
                    </div>
                    <div class="clear clearfix"></div>
                </div>
				<?php do_action( 'cbxuseronline_settings_form_after', 'settings' ); ?>
            </div>
        </div>
    </div>
</div>