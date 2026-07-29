<?php
/**
 * Plugin Name: Trivia Nationals Team Roster Google Sheets Sync
 * Description: Keeps the TN26 Team Roster Google Sheet synchronized with WordPress team assignments.
 * Version: 1.1.0
 * Author: Trivia Nationals
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class TN_Team_Roster_Sheets_Sync {
	private const OPTION_ENDPOINT = 'tn_roster_sheets_endpoint';
	private const OPTION_SECRET = 'tn_roster_sheets_secret';
	private const OPTION_LAST_SYNC = 'tn_roster_sheets_last_sync';
	private const OPTION_LAST_COUNT = 'tn_roster_sheets_last_count';
	private const OPTION_LAST_ERROR = 'tn_roster_sheets_last_error';
	private const ASYNC_HOOK = 'tn_roster_sheets_sync_snapshot';
	private const SETTINGS_SLUG = 'tn-roster-sheet-sync';
	private static bool $shutdown_sync_scheduled = false;

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'add_settings_page' ], 40 );
		add_action( 'admin_init', [ self::class, 'register_settings' ] );
		add_action( 'admin_post_tn_roster_sheets_sync_now', [ self::class, 'handle_manual_sync' ] );
		add_action( self::ASYNC_HOOK, [ self::class, 'run_queued_sync' ] );

		add_action( 'added_post_meta', [ self::class, 'meta_changed' ], 10, 4 );
		add_action( 'updated_post_meta', [ self::class, 'meta_changed' ], 10, 4 );
		add_action( 'deleted_post_meta', [ self::class, 'meta_changed' ], 10, 4 );
		add_action( 'transition_post_status', [ self::class, 'status_changed' ], 10, 3 );
		add_action( 'before_delete_post', [ self::class, 'before_delete' ] );
	}

	public static function add_settings_page(): void {
		add_submenu_page(
			'trivia-desc-editor',
			'Roster Sheet Sync',
			'Roster Sheet Sync',
			'manage_options',
			self::SETTINGS_SLUG,
			[ self::class, 'render_settings_page' ]
		);
	}

	public static function register_settings(): void {
		register_setting( 'tn_roster_sheets', self::OPTION_ENDPOINT, [
			'type' => 'string',
			'sanitize_callback' => 'esc_url_raw',
		] );
		register_setting( 'tn_roster_sheets', self::OPTION_SECRET, [
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		] );
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$last_sync = (string) get_option( self::OPTION_LAST_SYNC );
		$last_count = absint( get_option( self::OPTION_LAST_COUNT, 0 ) );
		$last_error = (string) get_option( self::OPTION_LAST_ERROR );
		?>
		<div class="wrap">
			<h1>Team Roster Google Sheet Sync</h1>
			<p>Roster changes automatically queue a complete snapshot of active teams. Team ID is the stable row key.</p>
			<?php if ( $last_error ) : ?>
				<div class="notice notice-error inline"><p><strong>Last sync failed:</strong> <?php echo esc_html( $last_error ); ?></p></div>
			<?php elseif ( $last_sync ) : ?>
				<div class="notice notice-success inline"><p>Last synced <?php echo esc_html( $last_sync ); ?> (<?php echo (int) $last_count; ?> teams).</p></div>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'tn_roster_sheets' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( self::OPTION_ENDPOINT ); ?>">Apps Script web app URL</label></th>
						<td><input class="regular-text code" type="url" id="<?php echo esc_attr( self::OPTION_ENDPOINT ); ?>" name="<?php echo esc_attr( self::OPTION_ENDPOINT ); ?>" value="<?php echo esc_attr( get_option( self::OPTION_ENDPOINT ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( self::OPTION_SECRET ); ?>">Shared secret</label></th>
						<td><input class="regular-text code" type="password" id="<?php echo esc_attr( self::OPTION_SECRET ); ?>" name="<?php echo esc_attr( self::OPTION_SECRET ); ?>" value="<?php echo esc_attr( get_option( self::OPTION_SECRET ) ); ?>" autocomplete="new-password"></td>
					</tr>
				</table>
				<?php submit_button( 'Save Sync Settings' ); ?>
			</form>
			<hr>
			<h2>Manual full sync</h2>
			<p>Use this after setup or whenever the Sheet needs to be rebuilt from WordPress.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tn_roster_sheets_sync_now">
				<?php wp_nonce_field( 'tn_roster_sheets_sync_now' ); ?>
				<?php submit_button( 'Sync Entire Roster Now', 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	public static function meta_changed( $meta_id, $object_id, $meta_key, $meta_value ): void {
		if ( get_post_type( $object_id ) !== 'tn_tde_signup' ) return;
		$watched = [
			'_tn_tde_signup_assigned_players',
			'_tn_tde_signup_team',
			'_tn_tde_signup_name',
			'_tn_tde_signup_email',
			'_tn_tde_signup_event_title',
			'_tn_tde_signup_flight',
			'_tn_tde_signup_status',
		];
		if ( ! in_array( $meta_key, $watched, true ) ) return;
		if ( $meta_key === '_tn_tde_signup_assigned_players' || $meta_key === '_tn_tde_signup_team' ) {
			update_post_meta( $object_id, '_tn_tde_signup_roster_updated_at', current_time( 'mysql' ) );
		}
		self::sync_at_shutdown();
	}

	public static function status_changed( $new_status, $old_status, $post ): void {
		if ( ! $post || $post->post_type !== 'tn_tde_signup' || $new_status === $old_status ) return;
		self::sync_at_shutdown();
	}

	public static function before_delete( $post_id ): void {
		if ( get_post_type( $post_id ) === 'tn_tde_signup' ) self::sync_at_shutdown();
	}

	private static function sync_at_shutdown(): void {
		if ( self::$shutdown_sync_scheduled ) return;
		self::$shutdown_sync_scheduled = true;
		add_action( 'shutdown', [ self::class, 'run_shutdown_sync' ] );
	}

	public static function run_shutdown_sync(): void {
		if ( ! self::sync() ) self::queue_sync( 15 * MINUTE_IN_SECONDS );
	}

	public static function queue_sync( int $delay = 0 ): void {
		$delay = max( 0, $delay );
		if ( function_exists( 'as_schedule_single_action' ) ) {
			if ( $delay === 0 && function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::ASYNC_HOOK, [], 'tn-roster-sheets' );
			}
			as_schedule_single_action( time() + $delay, self::ASYNC_HOOK, [], 'tn-roster-sheets', true );
			return;
		}
		if ( ! wp_next_scheduled( self::ASYNC_HOOK ) ) {
			wp_schedule_single_event( time() + $delay, self::ASYNC_HOOK );
		}
	}

	public static function run_queued_sync(): void {
		if ( ! self::sync() ) self::queue_sync( 15 * MINUTE_IN_SECONDS );
	}

	public static function handle_manual_sync(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'tn_roster_sheets_sync_now' );
		$ok = self::sync();
		if ( ! $ok ) self::queue_sync( 15 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg(
			'tn_roster_sheet_sync',
			$ok ? 'success' : 'failed',
			admin_url( 'admin.php?page=' . self::SETTINGS_SLUG )
		) );
		exit;
	}

	private static function sync(): bool {
		$endpoint = trim( (string) get_option( self::OPTION_ENDPOINT ) );
		$secret = trim( (string) get_option( self::OPTION_SECRET ) );
		if ( $endpoint === '' || $secret === '' ) {
			update_option( self::OPTION_LAST_ERROR, 'Missing Apps Script endpoint or shared secret.', false );
			return false;
		}
		$rows = self::snapshot_rows();
		$response = wp_remote_post( $endpoint, [
			'timeout' => 20,
			'redirection' => 0,
			'headers' => [ 'Content-Type' => 'application/json; charset=utf-8' ],
			'body' => wp_json_encode( [
				'secret' => $secret,
				'action' => 'team_roster_snapshot',
				'rows' => $rows,
			] ),
		] );
		if ( is_wp_error( $response ) ) {
			update_option( self::OPTION_LAST_ERROR, $response->get_error_message(), false );
			return false;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 300 && $code < 400 && wp_remote_retrieve_header( $response, 'location' ) ) {
			$response = wp_remote_get( wp_remote_retrieve_header( $response, 'location' ), [
				'timeout' => 20,
				'redirection' => 5,
			] );
			$code = is_wp_error( $response ) ? 500 : wp_remote_retrieve_response_code( $response );
		}
		$body = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );
		if ( $code < 200 || $code >= 300 || ! is_array( $result ) || empty( $result['ok'] ) ) {
			update_option( self::OPTION_LAST_ERROR, 'HTTP ' . $code . ': ' . substr( wp_strip_all_tags( $body ), 0, 300 ), false );
			return false;
		}
		update_option( self::OPTION_LAST_SYNC, current_time( 'mysql' ), false );
		update_option( self::OPTION_LAST_COUNT, count( $rows ), false );
		delete_option( self::OPTION_LAST_ERROR );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::ASYNC_HOOK, [], 'tn-roster-sheets' );
		}
		return true;
	}

	private static function snapshot_rows(): array {
		if (
			! function_exists( 'tn_tde_signup_meta_value' )
			|| ! function_exists( 'tn_tde_signup_assigned_player_ids' )
			|| ! function_exists( 'tn_tde_team_signup_admin_rows' )
		) {
			return [];
		}
		$attendees = [];
		if ( function_exists( 'tn_tickets_attendee_roster' ) ) {
			foreach ( tn_tickets_attendee_roster() as $person ) {
				if ( empty( $person['id'] ) ) continue;
				$attendees[ (string) $person['id'] ] = sanitize_text_field( $person['name'] ?? $person['preferred_name'] ?? '' );
			}
		}
		$rows = [];
		foreach ( tn_tde_team_signup_admin_rows() as $event_title => $signup_ids ) {
			foreach ( array_map( 'absint', $signup_ids ) as $signup_id ) {
				$assigned_ids = tn_tde_signup_assigned_player_ids( $signup_id );
				$team_name = tn_tde_signup_meta_value( $signup_id, 'team' );
				$player_names = [];
				foreach ( $assigned_ids as $player_id ) {
					if ( isset( $attendees[ $player_id ] ) && $attendees[ $player_id ] !== '' ) $player_names[] = $attendees[ $player_id ];
				}
				$updated_at = tn_tde_signup_meta_value( $signup_id, 'roster_updated_at' );
				if ( $updated_at === '' ) $updated_at = (string) get_post_field( 'post_modified', $signup_id );
				$rows[] = [
					'team_id' => (string) $signup_id,
					'team_name' => $team_name !== '' ? $team_name : 'Unnamed team',
					'captain' => tn_tde_signup_meta_value( $signup_id, 'name' ),
					'player_names' => implode( ', ', $player_names ),
					'contact_email' => tn_tde_signup_meta_value( $signup_id, 'email' ),
					'event' => $event_title,
					'flight' => tn_tde_signup_meta_value( $signup_id, 'flight' ),
					'status' => 'Active',
					'last_updated' => $updated_at,
				];
			}
		}
		usort( $rows, static function( $a, $b ) {
			$event = strcasecmp( $a['event'], $b['event'] );
			return $event !== 0 ? $event : strcasecmp( $a['team_name'], $b['team_name'] );
		} );
		return $rows;
	}
}

add_action( 'plugins_loaded', [ 'TN_Team_Roster_Sheets_Sync', 'init' ], 30 );
