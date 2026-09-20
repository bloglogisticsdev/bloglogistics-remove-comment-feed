<?php
/**
 * Main plugin class for BlogLogistics Remove Comment Feed.
 *
 * @package BlogLogistics_Remove_Comment_Feed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BlogLogistics_Remove_Comment_Feed', false ) ) {

	/**
	 * Remove comment feeds and provide a small settings UI.
	 */
	final class BlogLogistics_Remove_Comment_Feed {

		private const OPTION_NAME = 'bloglogistics_rcf_options';

		private const DEFAULT_MESSAGE = 'Comment feeds are not available on this website.';

		private const DEFAULT_RESPONSE_CODE = 404;

		/**
		 * Whether wp_head output buffering is currently active.
		 *
		 * @var bool
		 */
		private bool $head_buffering = false;

		/**
		 * Register hooks.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_post_bloglogistics_rcf_reset_message', array( $this, 'handle_reset_message' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( BLOGLOGISTICS_RCF_FILE ), array( $this, 'add_plugin_action_links' ) );

			if ( ! $this->is_enabled() ) {
				return;
			}

			add_filter( 'feed_links_show_comments_feed', '__return_false' );

			add_action( 'do_feed_rss2_comments', array( $this, 'block_comment_feed' ), 1 );
			add_action( 'do_feed_atom_comments', array( $this, 'block_comment_feed' ), 1 );
			add_action( 'template_redirect', array( $this, 'maybe_block_comment_feed_request' ), 0 );

			add_action( 'wp_head', array( $this, 'start_wp_head_buffer' ), 0 );
			add_action( 'wp_head', array( $this, 'end_wp_head_buffer' ), PHP_INT_MAX );
		}

		/**
		 * Default option values.
		 *
		 * @return array<string, mixed>
		 */
		private function defaults(): array {
			return array(
				'enabled'       => true,
				'message'       => self::DEFAULT_MESSAGE,
				'response_code' => self::DEFAULT_RESPONSE_CODE,
			);
		}

		/**
		 * Get plugin options merged with defaults.
		 *
		 * @return array<string, mixed>
		 */
		private function get_options(): array {
			$options = get_option( self::OPTION_NAME, array() );

			if ( ! is_array( $options ) ) {
				$options = array();
			}

			return wp_parse_args( $options, $this->defaults() );
		}

		/**
		 * Whether comment feeds should be disabled.
		 */
		private function is_enabled(): bool {
			$options = $this->get_options();
			return ! empty( $options['enabled'] );
		}

		/**
		 * Message shown to visitors who directly open a blocked comment feed URL.
		 */
		private function get_block_message(): string {
			$options = $this->get_options();
			$message = isset( $options['message'] ) ? (string) $options['message'] : '';
			$message = trim( $message );

			return '' !== $message ? $message : self::DEFAULT_MESSAGE;
		}

		/**
		 * HTTP response code used for blocked comment feeds.
		 */
		private function get_response_code(): int {
			$options       = $this->get_options();
			$response_code = isset( $options['response_code'] ) ? absint( $options['response_code'] ) : self::DEFAULT_RESPONSE_CODE;

			return in_array( $response_code, array( 404, 410 ), true ) ? $response_code : self::DEFAULT_RESPONSE_CODE;
		}

		/**
		 * Register settings.
		 */
		public function register_settings(): void {
			register_setting(
				'bloglogistics_rcf_options',
				self::OPTION_NAME,
				array(
					'type'              => 'array',
					'sanitize_callback' => array( $this, 'sanitize_options' ),
					'default'           => $this->defaults(),
					'show_in_rest'      => false,
				)
			);

			add_settings_section(
				'bloglogistics_rcf_main_section',
				esc_html__( 'Comment Feed Settings', 'bloglogistics-remove-comment-feed' ),
				array( $this, 'render_section_description' ),
				'bloglogistics_rcf'
			);

			add_settings_field(
				'bloglogistics_rcf_enabled',
				esc_html__( 'Disable comment feeds', 'bloglogistics-remove-comment-feed' ),
				array( $this, 'render_enabled_field' ),
				'bloglogistics_rcf',
				'bloglogistics_rcf_main_section'
			);

			add_settings_field(
				'bloglogistics_rcf_response_code',
				esc_html__( 'Blocked feed HTTP response', 'bloglogistics-remove-comment-feed' ),
				array( $this, 'render_response_code_field' ),
				'bloglogistics_rcf',
				'bloglogistics_rcf_main_section'
			);

			add_settings_field(
				'bloglogistics_rcf_message',
				esc_html__( 'Message shown for blocked comment feeds', 'bloglogistics-remove-comment-feed' ),
				array( $this, 'render_message_field' ),
				'bloglogistics_rcf',
				'bloglogistics_rcf_main_section'
			);
		}

		/**
		 * Sanitize saved options.
		 *
		 * @param mixed $input Raw option input.
		 * @return array<string, mixed>
		 */
		public function sanitize_options( $input ): array {
			$input = is_array( $input ) ? $input : array();

			$message = isset( $input['message'] ) ? sanitize_textarea_field( wp_unslash( $input['message'] ) ) : self::DEFAULT_MESSAGE;
			$message = trim( $message );

			$response_code = isset( $input['response_code'] ) ? absint( $input['response_code'] ) : self::DEFAULT_RESPONSE_CODE;

			if ( ! in_array( $response_code, array( 404, 410 ), true ) ) {
				$response_code = self::DEFAULT_RESPONSE_CODE;
			}

			return array(
				'enabled'       => ! empty( $input['enabled'] ),
				'message'       => '' !== $message ? $message : self::DEFAULT_MESSAGE,
				'response_code' => $response_code,
			);
		}

		/**
		 * Add the BlogLogistics admin menu and this plugin's settings page.
		 */
		public function add_admin_menu(): void {
			$this->register_bloglogistics_parent_menu();

			add_submenu_page(
				'bloglogistics',
				esc_html__( 'BlogLogistics Remove Comment Feed', 'bloglogistics-remove-comment-feed' ),
				esc_html__( 'Remove Comment Feed', 'bloglogistics-remove-comment-feed' ),
				'manage_options',
				'bloglogistics-remove-comment-feed',
				array( $this, 'render_settings_page' )
			);
		}

		/**
		 * Add a Settings shortcut on the Plugins screen.
		 *
		 * @param array<int|string, string> $links Existing plugin action links.
		 * @return array<int|string, string>
		 */
		public function add_plugin_action_links( array $links ): array {
			$settings_url  = admin_url( 'admin.php?page=bloglogistics-remove-comment-feed' );
			$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'bloglogistics-remove-comment-feed' ) . '</a>';

			array_unshift( $links, $settings_link );

			return $links;
		}

		/**
		 * Register the shared BlogLogistics parent menu if another BlogLogistics plugin has not already done so.
		 */
		private function register_bloglogistics_parent_menu(): void {
			if ( $this->bloglogistics_parent_menu_exists() ) {
				return;
			}

			add_menu_page(
				esc_html__( 'BlogLogistics', 'bloglogistics-remove-comment-feed' ),
				esc_html__( 'BlogLogistics', 'bloglogistics-remove-comment-feed' ),
				'manage_options',
				'bloglogistics',
				array( $this, 'render_bloglogistics_parent_page' ),
				'dashicons-rss',
				58
			);
		}

		/**
		 * Check whether the shared BlogLogistics parent menu already exists.
		 */
		private function bloglogistics_parent_menu_exists(): bool {
			global $menu;

			if ( ! is_array( $menu ) ) {
				return false;
			}

			foreach ( $menu as $menu_item ) {
				if ( isset( $menu_item[2] ) && 'bloglogistics' === $menu_item[2] ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Render the shared BlogLogistics parent menu page.
		 */
		public function render_bloglogistics_parent_page(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'BlogLogistics', 'bloglogistics-remove-comment-feed' ); ?></h1>
				<p><?php esc_html_e( 'Use the BlogLogistics submenu items to manage installed BlogLogistics plugins.', 'bloglogistics-remove-comment-feed' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Settings section description.
		 */
		public function render_section_description(): void {
			echo '<p>' . esc_html__( 'Remove comment feed links from your site and block direct access to comment feed URLs. Normal post feeds are not affected.', 'bloglogistics-remove-comment-feed' ) . '</p>';
		}

		/**
		 * Render checkbox field.
		 */
		public function render_enabled_field(): void {
			$options = $this->get_options();
			?>
			<label for="bloglogistics_rcf_enabled">
				<input type="checkbox" id="bloglogistics_rcf_enabled" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled]" value="1" <?php checked( ! empty( $options['enabled'] ) ); ?> />
				<?php esc_html_e( 'Stop visitors and bots from accessing comment RSS/Atom feeds.', 'bloglogistics-remove-comment-feed' ); ?>
			</label>
			<?php
		}

		/**
		 * Render HTTP response code field.
		 */
		public function render_response_code_field(): void {
			$response_code = $this->get_response_code();
			?>
			<select id="bloglogistics_rcf_response_code" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[response_code]">
				<option value="404" <?php selected( 404, $response_code ); ?>><?php esc_html_e( '404 Not Found', 'bloglogistics-remove-comment-feed' ); ?></option>
				<option value="410" <?php selected( 410, $response_code ); ?>><?php esc_html_e( '410 Gone', 'bloglogistics-remove-comment-feed' ); ?></option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Use 404 for the existing behaviour, or 410 to tell crawlers that comment feeds have been intentionally removed.', 'bloglogistics-remove-comment-feed' ); ?>
			</p>
			<?php
		}

		/**
		 * Render message field.
		 */
		public function render_message_field(): void {
			$options = $this->get_options();
			$message = isset( $options['message'] ) ? (string) $options['message'] : self::DEFAULT_MESSAGE;
			?>
			<textarea id="bloglogistics_rcf_message" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[message]" rows="4" cols="70" class="large-text"><?php echo esc_textarea( $message ); ?></textarea>
			<p class="description">
				<?php esc_html_e( 'This message is shown only if someone manually visits a comment feed URL, such as /comments/feed/. It does not create a page and it does not add any new links to your site.', 'bloglogistics-remove-comment-feed' ); ?>
			</p>
			<?php
		}

		/**
		 * Render settings page.
		 */
		public function render_settings_page(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$comment_feed_url = get_feed_link( 'comments_' . get_default_feed() );
			$normal_feed_url  = get_feed_link();
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'BlogLogistics Remove Comment Feed', 'bloglogistics-remove-comment-feed' ); ?></h1>

				<?php $this->render_feed_status(); ?>

				<form method="post" action="options.php">
					<?php
					settings_fields( 'bloglogistics_rcf_options' );
					do_settings_sections( 'bloglogistics_rcf' );
					submit_button();
					?>
				</form>

				<hr />

				<h2><?php esc_html_e( 'Feed Status & Tests', 'bloglogistics-remove-comment-feed' ); ?></h2>
				<p><?php esc_html_e( 'Open these links in a new tab to verify the current feed behaviour.', 'bloglogistics-remove-comment-feed' ); ?></p>
				<p>
					<a class="button button-secondary" href="<?php echo esc_url( $comment_feed_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Test comment feed', 'bloglogistics-remove-comment-feed' ); ?>
					</a>
					<a class="button button-secondary" href="<?php echo esc_url( $normal_feed_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Test normal feed', 'bloglogistics-remove-comment-feed' ); ?>
					</a>
				</p>
				<p class="description">
					<?php esc_html_e( 'When blocking is enabled, the comment feed should show the configured blocked-feed message while the normal WordPress feed remains available.', 'bloglogistics-remove-comment-feed' ); ?>
				</p>

				<hr />

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="bloglogistics_rcf_reset_message" />
					<?php wp_nonce_field( 'bloglogistics_rcf_reset_message' ); ?>
					<?php submit_button( esc_html__( 'Reset message to default', 'bloglogistics-remove-comment-feed' ), 'secondary', 'submit', false ); ?>
					<p class="description">
						<?php esc_html_e( 'Restores the default message shown when someone visits a blocked comment feed URL.', 'bloglogistics-remove-comment-feed' ); ?>
					</p>
				</form>
			</div>
			<?php
		}

		/**
		 * Render the current comment-feed status indicator.
		 */
		private function render_feed_status(): void {
			if ( $this->is_enabled() ) {
				?>
				<div class="notice notice-success inline">
					<p>
						<strong><?php esc_html_e( 'Comment feeds are currently disabled.', 'bloglogistics-remove-comment-feed' ); ?></strong>
						<?php echo ' '; ?><?php esc_html_e( 'Normal WordPress feeds remain available.', 'bloglogistics-remove-comment-feed' ); ?>
					</p>
				</div>
				<?php
				return;
			}
			?>
			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'Comment feeds are currently enabled.', 'bloglogistics-remove-comment-feed' ); ?></strong>
					<?php echo ' '; ?><?php esc_html_e( 'Enable blocking below to remove comment feed links and block direct comment feed requests.', 'bloglogistics-remove-comment-feed' ); ?>
				</p>
			</div>
			<?php
		}

		/**
		 * Reset the blocked-feed message to the default.
		 */
		public function handle_reset_message(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'bloglogistics-remove-comment-feed' ) );
			}

			check_admin_referer( 'bloglogistics_rcf_reset_message' );

			$options            = $this->get_options();
			$options['message'] = self::DEFAULT_MESSAGE;
			update_option( self::OPTION_NAME, $options );

			wp_safe_redirect( add_query_arg( 'settings-updated', 'true', admin_url( 'admin.php?page=bloglogistics-remove-comment-feed' ) ) );
			exit;
		}

		/**
		 * Block direct comment feed requests caught by WordPress feed actions.
		 */
		public function block_comment_feed(): void {
			$message_html = wpautop( esc_html( $this->get_block_message() ) );

			wp_die(
				$message_html,
				esc_html__( 'Comment Feed Disabled', 'bloglogistics-remove-comment-feed' ),
				array(
					'response' => $this->get_response_code(),
				)
			);
		}

		/**
		 * Block direct comment feed requests caught during the main request.
		 */
		public function maybe_block_comment_feed_request(): void {
			if ( $this->is_comment_feed_request() ) {
				$this->block_comment_feed();
			}
		}

		/**
		 * Determine whether the current request is for a comment feed.
		 */
		private function is_comment_feed_request(): bool {
			if ( function_exists( 'is_comment_feed' ) && is_comment_feed() ) {
				return true;
			}

			if ( function_exists( 'is_feed' ) && is_feed() ) {
				$withcomments = get_query_var( 'withcomments' );

				if ( ! empty( $withcomments ) ) {
					return true;
				}
			}

			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

			if ( '' === $request_uri ) {
				return false;
			}

			return (bool) preg_match( '#/comments/feed(?:/|$|\?)#i', $request_uri );
		}

		/**
		 * Start a small buffer around wp_head so only comment feed links are removed.
		 */
		public function start_wp_head_buffer(): void {
			if ( $this->head_buffering ) {
				return;
			}

			$this->head_buffering = true;
			ob_start();
		}

		/**
		 * End the wp_head buffer and remove comment feed links.
		 */
		public function end_wp_head_buffer(): void {
			if ( ! $this->head_buffering ) {
				return;
			}

			$this->head_buffering = false;
			$output = ob_get_clean();

			if ( false === $output ) {
				return;
			}

			echo $this->remove_comment_feed_links_from_html( $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Remove comment feed alternate links from a chunk of HTML.
		 *
		 * @param string $html HTML output.
		 */
		private function remove_comment_feed_links_from_html( string $html ): string {
			$patterns = array(
				'#<link\b[^>]*rel=["\']alternate["\'][^>]*href=["\'][^"\']*/comments/feed/?[^"\']*["\'][^>]*>\s*#i',
				'#<link\b[^>]*href=["\'][^"\']*/comments/feed/?[^"\']*["\'][^>]*rel=["\']alternate["\'][^>]*>\s*#i',
				'#<link\b[^>]*title=["\'][^"\']*comments?\s+feed[^"\']*["\'][^>]*>\s*#i',
			);

			return (string) preg_replace( $patterns, '', $html );
		}
	}
}
