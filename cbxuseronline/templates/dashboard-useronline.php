<?php
/**
 * This template provides the dashboard view of the plugin
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

<div class="wrap cbx-chota cbxuseronline-page-wrapper cbxuseronline-dashboard-wrapper" id="cbxuseronline-dashboard">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2></h2>
				<?php
				settings_errors();
				?>
				<?php do_action( 'cbxuseronline_wpheading_wrap_before', 'dashboard' ); ?>
                <div class="wp-heading-wrap">
                    <div class="wp-heading-wrap-left pull-left">
						<?php do_action( 'cbxuseronline_wpheading_wrap_left_before', 'dashboard' ); ?>
                        <h1 class="wp-heading-inline wp-heading-inline-cbxuseronline">
							<?php esc_html_e( 'User Online Dashboard', 'cbxuseronline' ); ?>
                            <a href="#" id="refreshtimenow_trig" class="button ml-10 button error ld-ext-right"><?php esc_attr_e( 'Refresh Now', 'cbxuseronline' ); ?><span class="ld ld-spin ld-ring"></span></a>
                        </h1>
						<?php do_action( 'cbxuseronline_wpheading_wrap_left_after', 'dashboard' ); ?>
                    </div>
                    <div class="wp-heading-wrap-right  pull-right">
						<?php do_action( 'cbxuseronline_wpheading_wrap_right_before', 'dashboard' ); ?>
                        <a href="<?php echo esc_url(admin_url( 'admin.php?page=cbxuseronline-settings' )); ?>"
                           class="button outline primary pull-right"><?php esc_html_e( 'Global Settings', 'cbxuseronline' ); ?></a>
						<?php do_action( 'cbxuseronline_wpheading_wrap_right_after', 'dashboard' ); ?>
                    </div>
                </div>
				<?php do_action( 'cbxuseronline_wpheading_wrap_after', 'dashboard' ); ?>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-12">
				<?php do_action( 'cbxuseronline_dashboard_listing_before', 'dashboard' ); ?>
                <div class="postbox">
                    <div class="inside">
                        <div class="clear clearfix"></div>
						<?php
						$page     = '';
						$userdata = CBXUseronlineHelper::user_online( $page );


						$output_members            = '';
						$output_online_count       = '';
						$output_online_count_parts = '';
						$output                    = '';

						$user_count = isset( $userdata['count'] ) ? intval( $userdata['count'] ) : 0;


						$output_online_count = CBXUseronlineHelper::get_correct_plugral_text( $user_count, __( 'Total <strong>%</strong> users', 'cbxuseronline' ), __( 'Total <strong>%</strong> user', 'cbxuseronline' ) );


						$members       = isset( $userdata['users_bygroup']['user'] ) ? $userdata['users_bygroup']['user'] : [];
						$members_count = sizeof( $members );

						if ( $output_online_count_parts != '' ) {

							$output_online_count_parts .= ',';

						}


						$output_online_count_parts .= CBXUseronlineHelper::get_correct_plugral_text( $members_count, __( ' <strong>%</strong> members', 'cbxuseronline' ), __( ' <strong>%</strong> member', 'cbxuseronline' ) );


						$guest        = isset( $userdata['users_bygroup']['guest'] ) ? $userdata['users_bygroup']['guest'] : [];
						$guests_count = sizeof( $guest );
						if ( $output_online_count_parts != '' ) {

							$output_online_count_parts .= ',';

						}


						$output_online_count_parts .= CBXUseronlineHelper::get_correct_plugral_text( $guests_count, __( ' <strong>%</strong> guests', 'cbxuseronline' ), __( ' <strong>%</strong> guest', 'cbxuseronline' ) );


						$bot        = isset( $userdata['users_bygroup']['bot'] ) ? $userdata['users_bygroup']['bot'] : [];
						$bots_count = sizeof( $bot );


						if ( $output_online_count_parts != '' ) {
							$output_online_count_parts .= ',';
						}

						$output_online_count_parts .= CBXUseronlineHelper::get_correct_plugral_text( $bots_count, __( ' <strong>%</strong> bots', 'cbxuseronline' ), __( ' <strong>%</strong> bot', 'cbxuseronline' ) );

						if ( $output_online_count_parts != '' ) {
							$output_online_count .= esc_html__( ' including', 'cbxuseronline' );
							$output_online_count .= $output_online_count_parts;
						}

						$output_online_count .= esc_html__( ' online', 'cbxuseronline' );
						if ( $page != '' ) {
							$output_online_count .= esc_html__( ' on this page', 'cbxuseronline' );
						}
						$output_online_count = '<p style="margin-bottom: 10px;">' . $output_online_count . '</p>';

						$mostuseronline_html = '';
						$mostuser            = get_option( 'cbxuseronline_mostonline', [] );

						$mostuser_count = isset( $mostuser['count'] ) ? intval( $mostuser['count'] ) : 0;
						$mostuser_date  = isset( $mostuser['date'] ) ? sanitize_text_field( $mostuser['date'] ) : '';

						$mysql_date = false;

						$date_format = esc_html( get_option( 'date_format', __( 'F j, Y' ) ) ); //phpcs:ignore WordPress.WP.I18n.MissingArgDomain
						$time_format = esc_html( get_option( 'time_format', __( 'g:i a' ) ) ); //phpcs:ignore WordPress.WP.I18n.MissingArgDomain

						/* translators: 1: Date 2. Time */
						$formatted_string = sprintf( esc_html_x( '%1$s @ %2$s', 'Date @ time', 'cbxuseronline' ), $date_format, $time_format );

						/*if ( $mysql_date ) {
							$mostuser_date = mysql2date( sprintf( esc_html_x( '%1$s @ %1$s', 'Date @ time','cbxuseronline' ), get_option( 'date_format', __( 'F j, Y', 'cbxuseronline' ) ), get_option( 'time_format', __( 'g:i a', 'cbxuseronline' ) ) ), $mostuser_date, true );
						} else {
							$mostuser_date = date_i18n( sprintf( esc_html_x( '%1$s @ %2$s', 'Date @ time', 'cbxuseronline' ), get_option( 'date_format', __( 'F j, Y', 'cbxuseronline' ) ), get_option( 'time_format', __( 'g:i a', 'cbxuseronline' ) ) ), $mostuser_date );
						}*/

						//if ( $mysql_date ) {
						//	$mostuser_date = mysql2date( $formatted_string, $mostuser_date, true );
						//} else {
							$mostuser_date = date_i18n( $formatted_string, $mostuser_date);
						//}

						/* translators: 1: Most user count 2. Most user countr date */
						$mostuseronline_html = '<p style="margin-bottom: 20px;">' . sprintf( wp_kses(__( 'Most users ever online were <strong>%1$d</strong>, on %2$s', 'cbxuseronline' ), ['strong' => []]), $mostuser_count, $mostuser_date ) . '</p>';

						$output .= $output_online_count . $mostuseronline_html;

						if ( isset( $userdata['users_bygroup']['user'] ) ) {

							$output .= '<table class="widefat widethin cbxuseronline_table_data" id="cbxuseronline_table_data">';

							$output .= '<thead>
	<tr>
		<th class="row-title">' . esc_attr__( 'Name', 'cbxuseronline' ) . '</th>
		<th>' . esc_attr__( 'Device', 'cbxuseronline' ) . '</th>		
		<th>' . esc_attr__( 'IP Address', 'cbxuseronline' ) . '</th>		
		<th>' . esc_attr__( 'Last Login', 'cbxuseronline' ) . '</th>		
		<th>' . esc_attr__( 'Current Page', 'cbxuseronline' ) . '</th>		
	</tr>
	</thead>';

							$output .= '<tbody>';

							$i = 0;
							foreach ( $userdata['users_bygroup']['user'] as $member ) {
								$member_name       = $member->user_name;
								$user_profile_link = get_author_posts_url( $member->userid );
								$user_profile_link = get_author_posts_url( $member->userid );
								$user_profile_link = apply_filters( 'cbxuseronline_member_profile_link', $user_profile_link, $member->userid );

								$mobile_label = ( $member->mobile ) ? esc_html__( 'Mobile', 'cbxuseronline' ) : esc_html__( 'Desktop/Large', 'cbxuseronline' );

								$user_ip    = esc_html( $member->user_ip );
								$timestamp  = esc_html( $member->timestamp );
								$user_agent = esc_html( $member->user_agent );

								$page_title = esc_attr( $member->page_title );
								$page_url   = esc_url( $member->page_url );

								$alternate_class = ( $i % 2 == 0 ) ? 'alternate' : '';
								$i ++;
								$output .= '<tr class="' . esc_attr( $alternate_class ) . '">
									<td class="row-title"><label for="tablecell"><a target="_blank" href="' . esc_url( $user_profile_link ) . '">' . wp_unslash( $member_name ) . '</a></label></td>
									<td>' . esc_attr( $mobile_label ) . '</td>									
									<td>' . esc_attr( $user_ip ) . '</td>									
									<td>' . esc_attr( $timestamp ) . '</td>									
									<td><a target="_blank" href="' . $page_url . '">' . esc_attr( $page_title ) . '</a></td>									
								</tr>';
							}

							$output .= '</tbody>';
							$output .= '<tfoot>
	<tr>
		<th class="row-title">' . esc_attr__( 'Name', 'cbxuseronline' ) . '</th>
		<th>' . esc_attr__( 'Device', 'cbxuseronline' ) . '</th>		
		<th>' . esc_attr__( 'IP Address', 'cbxuseronline' ) . '</th>		
		<th>' . esc_attr__( 'Last Login', 'cbxuseronline' ) . '</th>	
		<th>' . esc_attr__( 'Current Page', 'cbxuseronline' ) . '</th>	
	</tr>
	</tfoot>
</table>';
						}

						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $output;
						?>

                        <div class="clear clearfix"></div>
                    </div>
                </div>
				<?php do_action( 'cbxuseronline_dashboard_listing_after', 'dashboard' ); ?>
            </div>
        </div>
    </div>
</div>