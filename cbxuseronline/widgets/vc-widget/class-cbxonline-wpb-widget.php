<?php
// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class CBXOnlineWPBWidget extends WPBakeryShortCode {

	/**
	 * CBXOnlineWPBWidget constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'cbx_user_online_mapping' ], 12 );
	}// /end of constructor


	/**
	 * Element Mapping
	 */
	public function cbx_user_online_mapping() {

		// Map the block with vc_map()
		vc_map( [
			"name"        => esc_html__( "Online User", 'cbxuseronline' ),
			"description" => esc_html__( "This widget shows online users based on widget setting",
				'cbxuseronline' ),
			"base"        => "cbxuseronline",
			"icon"        => CBX_USERONLINE_PLUGIN_ROOT_URL . 'assets/images/widget_icons/useronline-icon.svg',
			"category"    => esc_html__( 'CBX User Online', 'cbxuseronline' ),
			"params"      => [
				[
					"type"        => "checkbox",
					'admin_label' => true,
					"heading"     => esc_html__( "Show Memberlist", 'cbxuseronline' ),
					"param_name"  => "memberlist",
					'value'       => [
						'' => 1,
					],
					'std'         => 1,
				],
				[
					"type"        => "checkbox",
					'admin_label' => true,
					"heading"     => esc_html__( "Link user to author page", 'cbxuseronline' ),
					"param_name"  => "linkusername",
					'value'       => [
						'' => 1,
					],
					'std'         => 1,
				],
				[
					"type"        => "checkbox",
					'admin_label' => true,
					"heading"     => esc_html__( "Show online count", 'cbxuseronline' ),
					"param_name"  => "count",
					'value'       => [
						'' => 1,
					],
					'std'         => 1,
				],
				[
					"type"        => "checkbox",
					'admin_label' => true,
					"heading"     => esc_html__( "Show individual count", 'cbxuseronline' ),
					"param_name"  => "individual",
					'value'       => [
						'' => 1,
					],
					'std'         => 1,
				],
				[
					"type"        => "checkbox",
					'admin_label' => true,
					"heading"     => esc_html__( "Show member count", 'cbxuseronline' ),
					"param_name"  => "member_count",
					'value'       => [
						'' => 1,
					],
					'std'         => 1,
				],
				[
					"type"        => "checkbox",
					'admin_label' => true,
					"heading"     => esc_html__( "Show guest count", 'cbxuseronline' ),
					"param_name"  => "guest_count",
					'value'       => [
						'' => 1,
					],
					'std'         => 1,
				],
				[
					"type"        => "checkbox",
					'admin_label' => true,
					"heading"     => esc_html__( "Show bot count", 'cbxuseronline' ),
					"param_name"  => "bot_count",
					'value'       => [
						'' => 1,
					],
					'std'         => 1,
				],
				[
					"type"        => "checkbox",
					'admin_label' => true,
					"heading"     => esc_html__( "Show for current page", 'cbxuseronline' ),
					"param_name"  => "page",
					'value'       => [
						'' => 1,
					],
					'std'         => 1,
				],
				[
					"type"        => "checkbox",
					'admin_label' => true,
					"heading"     => esc_html__( "Show most user online", 'cbxuseronline' ),
					"param_name"  => "mostuseronline",
					'value'       => [
						'' => 1,
					],
					'std'         => 1,
				],
				[
					"type"        => "checkbox",
					'admin_label' => true,
					"heading"     => esc_html__( "Show mobile or desktop logged in status", 'cbxuseronline' ),
					"param_name"  => "mobile",
					'value'       => [
						'' => 1,
					],
					'std'         => 1,
				],
			]
		] );
	}
}// end class CBXOnlineWPBWidget

new CBXOnlineWPBWidget();