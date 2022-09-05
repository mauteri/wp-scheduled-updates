<?php
/**
 * @package wp-scheduled-updates
 */

namespace WP_Scheduled_Updates;

/**
 * Class Setup.
 */
class Setup {

	const PREFIX = 'wsu';
	private $post_types = array( 'post', 'page' );

	/**
	 * Construct.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_updates' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'force_private' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'block_enqueue_scripts' ) );
		add_filter( 'default_content', array( $this, 'default_content' ) );
		add_filter( 'default_title', array( $this, 'default_title' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
	}

	public function save_post( $post_id, $post ) {
		$meta_post_id = filter_input( INPUT_GET, 'wp_scheduled_post', FILTER_SANITIZE_STRING );
		$meta         = get_post_meta( $post_id, 'wsu_update_post_id', true );

		if ( intval( $meta_post_id ) && empty( $meta ) ) {
			update_post_meta( $post_id, 'wsu_update_post_id', (string) $meta_post_id );
		}
	}

	public function default_content( $content ) {
		$post_id = filter_input( INPUT_GET, 'wp_scheduled_post', FILTER_SANITIZE_STRING );

		if ( intval( $post_id ) ) {
			$post = get_post( $post_id );
			return $post->post_content;
		}

		return $content;
	}

	public function default_title( $title ) {
		$post_id = filter_input( INPUT_GET, 'wp_scheduled_post', FILTER_SANITIZE_STRING );

		if ( intval( $post_id ) ) {
			$post = get_post( $post_id );
			return $post->post_title;
		}

		return $title;
	}

	public function register_updates() {
		global $wp_post_types;

		$post_types = $this->post_types;
		$default_meta_value = null !== filter_input( INPUT_GET, 'wp_scheduled_post', FILTER_SANITIZE_STRING ) ? intval( filter_input( INPUT_GET, 'wp_scheduled_post', FILTER_SANITIZE_STRING ) ) : '';

		foreach ( $post_types as $post_type ) {
			$post_type_settings = $wp_post_types[ $post_type ];
			$post_type_name     = sprintf( '%s-%s', self::PREFIX, $post_type );

			$post_type_settings = array(
				'labels'       => array(
					'name'               => sprintf(
						_x( 'Scheduled Updates (%s)', 'Post Type General Name', 'wp-scheduled-updates' ), $post_type_settings->label
					),
					'singular_name'      => _x( 'Scheduled Update', 'Post Type Singular Name', 'wp-scheduled-updates' ),
					'menu_name'          => __( 'Scheduled Updates', 'wp-scheduled-updates' ),
					'all_items'          => __( 'Scheduled Updates', 'wp-scheduled-updates' ),
					'view_item'          => __( 'View Scheduled Update', 'wp-scheduled-updates' ),
					'add_new_item'       => __( 'Add New Scheduled Update', 'wp-scheduled-updates' ),
					'add_new'            => __( 'Add New', 'wp-scheduled-updates' ),
					'edit_item'          => __( 'Edit Scheduled Update', 'wp-scheduled-updates' ),
					'update_item'        => __( 'Update Scheduled Update', 'wp-scheduled-updates' ),
					'search_items'       => __( 'Search Scheduled Updates', 'wp-scheduled-updates' ),
					'not_found'          => __( 'Not Found', 'wp-scheduled-updates' ),
					'not_found_in_trash' => __( 'Not found in Trash', 'wp-scheduled-updates' ),
				),
				'public'       => true,
				'supports'     => array(
					'title',
					'editor',
					'custom-fields',
				),
				'show_in_rest' => $post_type_settings->show_in_rest,
				'template'     => $post_type_settings->template,
			);

			$post_type_settings['show_in_menu'] = sprintf( 'edit.php?post_type=%s', $post_type );

			if ( 'post' === $post_type ) {
				$post_type_settings['show_in_menu'] = 'edit.php';
			}

			register_post_type(
				$post_type_name,
				$post_type_settings
			);

			register_post_meta(
				$post_type_name,
				'wsu_update_post_id',
				[
					'auth_callback' => function() {
						return current_user_can( 'edit_posts' );
					},
					'sanitize_callback' => 'sanitize_text_field',
					'show_in_rest'      => array(
						'schema' => array(
							'type'    => 'string',
							'default' => (string) $default_meta_value,
						),
					),
					'single'            => true,
					'type'              => 'string',
				]
			);

			add_action( sprintf( 'publish_%s', $post_type_name ), array( $this, 'update_content' ), 10, 2 );
			add_action( sprintf( 'private_%s', $post_type_name ), array( $this, 'update_content' ), 10, 2 );
		}
	}

	public function force_private( $post ) {
		if ( 0 !== strpos( $post['post_type'], self::PREFIX . '-' ) ) {
			return $post;
		}
		if ( 'private' === $post['post_status'] ) {
			$current_date = strtotime( wp_date( 'Y-m-d H:i:s' ) );
			$publish_date = strtotime( $post['post_date'] );

			if ( $publish_date > $current_date ) {
				$post['post_status'] = 'future';
			}

			return $post;
		}

		if ( 'publish' === $post['post_status'] && empty( $post['post_password'] ) ) {
			$post['post_status'] = 'private';
		}

		return $post;
	}

	public function update_content( $post_id, $post ) {
		$update_post_id = intval( get_post_meta( $post_id, 'wsu_update_post_id', true ) );

		if ( ! empty( $update_post_id ) ) {
			$update_post = get_post( $update_post_id );
			$update_post->post_content = $post->post_content;
			wp_update_post( $update_post );
		}
	}

	public function block_enqueue_scripts() {
		$post  = get_post();

		if ( ! is_a( $post, '\WP_Post' ) ) {
			return;
		}

		$asset = require WP_SCHEDULED_UPDATES_PATH . '/assets/build/index.asset.php';
		wp_enqueue_script(
			'wp-scheduled-updates',
			WP_SCHEDULED_UPDATES_URL . 'assets/build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_localize_script(
			'wp-scheduled-updates',
			'wp_scheduled_updates',
			array(
				'admin_url'         => admin_url(),
				'current_post'      => $post->ID,
				'post_type'         => $post->post_type,
				'enabled_post_type' => in_array( $post->post_type, $this->post_types, true ),
				'wsc_post_type'     => ( 0 === strpos( $post->post_type, self::PREFIX . '-' ) ),
				'update_post_id'    => null !== filter_input( INPUT_GET, 'wp_scheduled_post', FILTER_SANITIZE_STRING ) ? intval( filter_input( INPUT_GET, 'wp_scheduled_post', FILTER_SANITIZE_STRING ) ) : ''
			)
		);
	}

}
