<?php
/**
 * Plugin Name:       BlogLogistics Remove Comment Feed
 * Plugin URI:        https://github.com/bloglogisticsdev/bloglogistics-remove-comment-feed
 * Description:       Removes comment feed links and blocks direct access to WordPress comment feed URLs while leaving normal post feeds available.
 * Version:           1.2.2
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            BlogLogistics
 * Author URI:        https://www.bloglogistics.com/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:        https://github.com/bloglogisticsdev/bloglogistics-remove-comment-feed
 * Text Domain:       bloglogistics-remove-comment-feed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLOGLOGISTICS_RCF_VERSION', '1.2.2' );
define( 'BLOGLOGISTICS_RCF_SLUG', 'bloglogistics-remove-comment-feed' );
define( 'BLOGLOGISTICS_RCF_FILE', __FILE__ );
define( 'BLOGLOGISTICS_RCF_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLOGLOGISTICS_RCF_REPO_URL', 'https://github.com/bloglogisticsdev/bloglogistics-remove-comment-feed/' );
define( 'BLOGLOGISTICS_RCF_UPDATE_MANIFEST_URL', 'https://updates.bloglogistics.com/plugins/bloglogistics-remove-comment-feed.json' );

$bloglogistics_rcf_puc = BLOGLOGISTICS_RCF_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

if ( file_exists( $bloglogistics_rcf_puc ) ) {
	if ( ! class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory', false ) ) {
		require_once $bloglogistics_rcf_puc;
	}

	require_once BLOGLOGISTICS_RCF_DIR . 'includes/class-bloglogistics-remove-comment-feed-updater.php';

	if ( class_exists( 'BlogLogistics_Remove_Comment_Feed_Updater', false ) ) {
		BlogLogistics_Remove_Comment_Feed_Updater::init(
			array(
				'repo_url'    => BLOGLOGISTICS_RCF_UPDATE_MANIFEST_URL,
				'plugin_file' => BLOGLOGISTICS_RCF_FILE,
				'slug'        => BLOGLOGISTICS_RCF_SLUG,
			)
		);
	}
}

if ( ! class_exists( 'BlogLogistics_Remove_Comment_Feed', false ) ) {

	/**
	 * Remove comment feeds and provide a small settings UI.
	 */
	final class BlogLogistics_Remove_Comment_Feed {

		private const OPTION_NAME = 'bloglogistics_rcf_options';

		private const DEFAULT_MESSAGE = 'Comment feeds are not available on this website.';

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
				'enabled' => true,
				'message' => self::DEFAULT_MESSAGE,
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

			return array(
				'enabled' => ! empty( $input['enabled'] ),
				'message' => '' !== $message ? $message : self::DEFAULT_MESSAGE,
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
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'BlogLogistics Remove Comment Feed', 'bloglogistics-remove-comment-feed' ); ?></h1>

				<form method="post" action="options.php">
					<?php
					settings_fields( 'bloglogistics_rcf_options' );
					do_settings_sections( 'bloglogistics_rcf' );
					submit_button();
					?>
				</form>

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
		 * Reset the blocked-feed message to the default.
		 */
		public function handle_reset_message(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'bloglogistics-remove-comment-feed' ) );
			}

			check_admin_referer( 'bloglogistics_rcf_reset_message' );

			$options = $this->get_options();
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
					'response' => 404,
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

new BlogLogistics_Remove_Comment_Feed();
