<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class ST_Admin_Notes_Admin {

	private $plugin_name;
	private $version;
	private $option_key = 'st_admin_notes_items';
	private $settings_key = 'st_admin_notes_settings';

	private function get_capability() {
		return apply_filters( 'st_admin_notes_capability', 'edit_posts' );
	}

	private function current_screen_id() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return '';
		}
		$screen = get_current_screen();
		return $screen ? $screen->id : '';
	}

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function enqueue_styles() {

		if ( ! current_user_can( $this->get_capability() ) ) {
			return;
		}
		wp_enqueue_style($this->plugin_name,plugin_dir_url( __FILE__ ) . 'css/st-admin-notes-admin.css',array(),filemtime( plugin_dir_path( __FILE__ ) . 'css/st-admin-notes-admin.css' ),'all');
	}

	public function enqueue_scripts() {

		if ( ! current_user_can( $this->get_capability() ) ) {
			return;
		}

		$notes    = $this->get_notes();
		$settings = $this->get_settings();
		$screen   = $this->current_screen_id();
		$current_user  = wp_get_current_user();
		$current_roles = (array) $current_user->roles;

		if ( ! empty( $settings['allowed_roles'] ) && empty( array_intersect( $current_roles, $settings['allowed_roles'] ) ) ) {
			return;
		}

	$js_file = plugin_dir_path( __FILE__ ) . 'js/st-admin-notes-admin.js';
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/st-admin-notes-admin.js',
			array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-resizable' ),
			file_exists( $js_file ) ? filemtime( $js_file ) : $this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'st_admin_notes_data',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'st_admin_notes_nonce' ),
				'notes'     => array_values( $this->filter_active_notes( $notes ) ),
				'settings'  => $settings,
				'palette'   => $this->get_palette(),
				'screenId'  => $screen,
				'userRoles' => $current_roles,
				'canManage' => current_user_can( $this->get_capability() ),
				'manageUrl' => admin_url( 'admin.php?page=st-admin-notes' ),
			)
		);

	}

	public function add_menu_pages() {
		$cap = $this->get_capability();

		add_menu_page(
			__( 'ST Admin Notes', 'st-admin-notes' ),
			__( 'ST Admin Notes', 'st-admin-notes' ),
			$cap,
			'st-admin-notes',
			array( $this, 'render_notes_page' ),
			'dashicons-sticky',
			65
		);

		add_submenu_page(
			'st-admin-notes',
			__( 'Notes', 'st-admin-notes' ),
			__( 'Notes', 'st-admin-notes' ),
			$cap,
			'st-admin-notes',
			array( $this, 'render_notes_page' )
		);
	}

	public function render_notes_page() {
		if ( ! current_user_can( $this->get_capability() ) ) {
			return;
		}

		$messages = array();
		// if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['st_admin_notes_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['st_admin_notes_nonce_field'] ) ), 'st_admin_notes_nonce' ) ) {
		if (isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['st_admin_notes_nonce_field'] ) && wp_verify_nonce(sanitize_text_field( wp_unslash( $_POST['st_admin_notes_nonce_field'] ) ),'st_admin_notes_nonce')) {
			$action = isset( $_POST['stn_action'] ) ? sanitize_text_field( wp_unslash( $_POST['stn_action'] ) ) : '';
			if ( 'add_note' === $action ) {
				$this->handle_add_note();
				$messages[] = __( 'Note added.', 'st-admin-notes' );
			} elseif ( 'edit_note' === $action ) {
				$this->handle_edit_note();
				$messages[] = __( 'Note updated.', 'st-admin-notes' );
			} elseif ( 'delete_note' === $action ) {
				$this->handle_delete_note();
				$messages[] = __( 'Note deleted.', 'st-admin-notes' );
			} elseif ( 'toggle_active' === $action ) {
				$this->handle_toggle_active();
				$messages[] = __( 'Note updated.', 'st-admin-notes' );
			}
		}

		$notes      = $this->get_notes();
		$palette    = $this->get_palette();
		$edit_id    = isset( $_GET['edit'] ) ? sanitize_text_field( wp_unslash( $_GET['edit'] ) ) : '';
		$edit_note  = null;
		if ( $edit_id ) {
			foreach ( $notes as $n ) {
				if ( $n['id'] === $edit_id ) {
					$edit_note = $n;
					break;
				}
			}
		}


		
		
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ST Admin Notes', 'st-admin-notes' ); ?></h1>
			<?php foreach ( $messages as $msg ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
			<?php endforeach; ?>

			<div class="st-notes-admin-card">
				<h2><?php echo $edit_note ? esc_html__( 'Edit Note', 'st-admin-notes' ) : esc_html__( 'Create Note', 'st-admin-notes' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'st_admin_notes_nonce', 'st_admin_notes_nonce_field' ); ?>
					<input type="hidden" name="stn_action" value="<?php echo $edit_note ? 'edit_note' : 'add_note'; ?>" />
					<?php if ( $edit_note ) : ?>
						<input type="hidden" name="id" value="<?php echo esc_attr( $edit_note['id'] ); ?>" />
					<?php endif; ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="stn-title"><?php esc_html_e( 'Title', 'st-admin-notes' ); ?></label></th>
							<td><input type="text" id="stn-title" name="title" class="regular-text" required value="<?php echo esc_attr( $edit_note ? $edit_note['title'] : '' ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="stn-content"><?php esc_html_e( 'Content', 'st-admin-notes' ); ?></label></th>
							<td><textarea id="stn-content" name="content" class="large-text" rows="4" required><?php echo esc_textarea( $edit_note ? $edit_note['content'] : '' ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><label for="stn-color"><?php esc_html_e( 'Color', 'st-admin-notes' ); ?></label></th>
							<td>
								<select id="stn-color" name="color">
									<?php foreach ( $palette as $key => $color ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $edit_note ? $edit_note['color'] : $this->get_settings()['default_color'], $key ); ?>><?php echo esc_html( ucfirst( $key ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Active', 'st-admin-notes' ); ?></th>
							<td><label><input type="checkbox" name="active" value="1" <?php checked( $edit_note ? $edit_note['active'] : true ); ?> /> <?php esc_html_e( 'Show on admin screens', 'st-admin-notes' ); ?></label></td>
						</tr>
					</table>
					<?php submit_button( $edit_note ? __( 'Update Note', 'st-admin-notes' ) : __( 'Add Note', 'st-admin-notes' ) ); ?>
				</form>
			</div>

			<div class="st-notes-admin-card">
				<h2><?php esc_html_e( 'Notes', 'st-admin-notes' ); ?></h2>
				<?php if ( empty( $notes ) ) : ?>
					<p><?php esc_html_e( 'No notes yet.', 'st-admin-notes' ); ?></p>
				<?php else : ?>
					<table class="widefat striped st-notes-admin-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'st-admin-notes' ); ?></th>
								<th><?php esc_html_e( 'Active', 'st-admin-notes' ); ?></th>
								<th><?php esc_html_e( 'Color', 'st-admin-notes' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'st-admin-notes' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $notes as $note ) : ?>
								<tr>
									<td><?php echo esc_html( $note['title'] ); ?></td>
									<td><?php echo ! empty( $note['active'] ) ? esc_html__( 'Yes', 'st-admin-notes' ) : esc_html__( 'No', 'st-admin-notes' ); ?></td>
									<td><span class="stn-color-chip" style="background:<?php echo esc_attr( $this->resolve_color( $note['color'] ) ); ?>"></span> <?php echo esc_html( ucfirst( $note['color'] ) ); ?></td>
									<td class="stn-actions">
										<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'st-admin-notes', 'edit' => $note['id'] ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'st-admin-notes' ); ?></a>
										
										<form method="post" style="display:inline-block;">
											<?php wp_nonce_field( 'st_admin_notes_nonce', 'st_admin_notes_nonce_field' ); ?>
											<input type="hidden" name="stn_action" value="toggle_active" />
											<input type="hidden" name="id" value="<?php echo esc_attr( $note['id'] ); ?>" />
											<input type="hidden" name="active" value="<?php echo empty( $note['active'] ) ? '1' : '0'; ?>" />
											<button class="button button-secondary" type="submit"><?php echo empty( $note['active'] ) ? esc_html__( 'Activate', 'st-admin-notes' ) : esc_html__( 'Deactivate', 'st-admin-notes' ); ?></button>
										</form>
										<form method="post" style="display:inline-block;margin-left:6px;">
											<?php wp_nonce_field( 'st_admin_notes_nonce', 'st_admin_notes_nonce_field' ); ?>
											<input type="hidden" name="stn_action" value="delete_note" />
											<input type="hidden" name="id" value="<?php echo esc_attr( $note['id'] ); ?>" />
											<button class="button button-link-delete" type="submit" onclick="return confirm('<?php echo esc_js( __( 'Delete this note?', 'st-admin-notes' ) ); ?>');"><?php esc_html_e( 'Delete', 'st-admin-notes' ); ?></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public function render_canvas() {
		if ( ! current_user_can( $this->get_capability() ) ) {
			return;
		}
		echo '<div id="st-admin-notes-canvas" class="st-admin-notes-canvas" aria-live="polite"></div>';
		echo '<button type="button" id="st-admin-notes-toggle" class="st-admin-notes-toggle" aria-expanded="true">' . esc_html__( 'Notes', 'st-admin-notes' ) . '</button>';
		?>
		<div id="stn-modal" class="stn-modal" aria-hidden="true">
			<div class="stn-modal__backdrop"></div>
			<div class="stn-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="stn-modal-title">
				<div class="stn-modal__header">
					<h2 id="stn-modal-title"><?php esc_html_e( 'Add Note', 'st-admin-notes' ); ?></h2>
					<button type="button" class="stn-modal__close" aria-label="<?php esc_attr_e( 'Close', 'st-admin-notes' ); ?>">×</button>
				</div>
				<form id="stn-modal-form">
					<p><label><?php esc_html_e( 'Title', 'st-admin-notes' ); ?><br/><input type="text" name="title" class="regular-text" required></label></p>
					<p><label><?php esc_html_e( 'Content', 'st-admin-notes' ); ?><br/><textarea name="content" rows="4" class="large-text" required></textarea></label></p>
					<p><label><?php esc_html_e( 'Color', 'st-admin-notes' ); ?><br/>
						<select name="color">
							<?php foreach ( $this->get_palette() as $key => $color ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( ucfirst( $key ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</label></p>
					<p><label><input type="checkbox" name="active" value="1" checked> <?php esc_html_e( 'Active', 'st-admin-notes' ); ?></label></p>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save note', 'st-admin-notes' ); ?></button></p>
				</form>
			</div>
		</div>
		<?php
	}

	public function ajax_add_note() {
		$this->verify_permissions();
		check_ajax_referer( 'st_admin_notes_nonce', 'nonce' );

		$note    = $this->sanitize_note_from_request();
		$notes   = $this->get_notes();
		$notes[] = $note;
		$this->save_notes( $notes );

		wp_send_json_success(
			array(
				'note' => $note,
			)
		);
	}

	public function ajax_fetch_notes() {
		$this->verify_permissions();
		check_ajax_referer( 'st_admin_notes_nonce', 'nonce' );

		$notes    = $this->get_notes();
		$settings = $this->get_settings();

		wp_send_json_success(
			array(
				'notes'    => array_values( $this->filter_active_notes( $notes ) ),
				'settings' => $settings,
				'palette'  => $this->get_palette(),
			)
		);
	}

	public function ajax_update_note() {
		$this->verify_permissions();
		check_ajax_referer( 'st_admin_notes_nonce', 'nonce' );

		$id    = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$notes = $this->get_notes();
		
		// Extract and sanitize only the fields we need
		$sanitized_data = array();
		
		// Text fields
		$text_fields = array( 'title', 'color' );
		foreach ( $text_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$sanitized_data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			}
		}
		
		// Textarea field
		if ( isset( $_POST['content'] ) ) {
			$sanitized_data['content'] = sanitize_textarea_field( wp_unslash( $_POST['content'] ) );
		}
		
		// Boolean fields
		$boolean_fields = array( 'hidden', 'minimized', 'active', 'archived' );
		foreach ( $boolean_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$sanitized_data[ $field ] = (bool) $_POST[ $field ];
			}
		}
		
		// Numeric fields
		$numeric_fields = array( 'x', 'y', 'width', 'height' );
		foreach ( $numeric_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$sanitized_data[ $field ] = absint( $_POST[ $field ] );
			}
		}
		
		foreach ( $notes as $index => $note ) {
			if ( $note['id'] === $id ) {
				$notes[ $index ] = $this->merge_note_update( $note, $sanitized_data );
				$this->save_notes( $notes );
				wp_send_json_success( array( 'note' => $notes[ $index ] ) );
			}
		}

		wp_send_json_error( array( 'message' => __( 'Note not found.', 'st-admin-notes' ) ), 404 );
	}

	public function ajax_delete_note() {
		$this->verify_permissions();
		check_ajax_referer( 'st_admin_notes_nonce', 'nonce' );

		$id    = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$notes = $this->get_notes();
		$notes = array_values(
			array_filter(
				$notes,
				function ( $note ) use ( $id ) {
					return $note['id'] !== $id;
				}
			)
		);
		$this->save_notes( $notes );

		wp_send_json_success();
	}

	private function verify_permissions() {
		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'st-admin-notes' ) ), 403 );
		}
	}

	private function get_notes() {
		$notes = get_option( $this->option_key, array() );
		return is_array( $notes ) ? $notes : array();
	}

	private function get_settings() {
		$settings = get_option(
			$this->settings_key,
			array(
				'overlay_enabled' => true,
				'default_color'   => 'yellow',
				'excluded_screens' => array(),
				'allowed_roles'    => array(),
			)
		);

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings = wp_parse_args(
			$settings,
			array(
				'overlay_enabled' => true,
				'default_color'   => 'yellow',
				'excluded_screens' => array(),
				'allowed_roles'    => array(),
			)
		);

		return $settings;
	}

	private function save_notes( $notes ) {
		update_option( $this->option_key, array_values( $notes ) );
	}

	private function filter_active_notes( $notes ) {
		return array_values(
			array_filter(
				$notes,
				function ( $note ) {
					return ! empty( $note['active'] ) && empty( $note['archived'] );
				}
			)
		);
	}

	private function sanitize_note_from_request() {
	if (isset( $_POST['st_admin_notes_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['st_admin_notes_nonce_field'] ) ),'st_admin_notes_nonce')) {

		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$content = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';
		$color   = isset( $_POST['color'] ) ? sanitize_text_field( wp_unslash( $_POST['color'] ) ) : $this->get_settings()['default_color'];
		$active  = isset( $_POST['active'] ) ? (bool) $_POST['active'] : false;
		return array(
			'id'        => wp_generate_uuid4(),
			'title'     => $title,
			'content'   => $content,
			'color'     => $color,
			'active'    => $active,
			'hidden'    => false,
			'minimized' => false,
			'archived'  => false,
			'x'         => 40,
			'y'         => 120,
			'width'     => 260,
			'height'    => 200,
			'author'    => get_current_user_id(),
		);
		};
	}

	private function merge_note_update( $note, $payload ) {
		$fields = array( 'title', 'content', 'color', 'hidden', 'minimized', 'active', 'archived' );

		foreach ( $fields as $field ) {
			if ( isset( $payload[ $field ] ) ) {
				$value = wp_unslash( $payload[ $field ] );
				if ( 'content' === $field ) {
					$value = sanitize_textarea_field( $value );
				} elseif ( in_array( $field, array( 'hidden', 'minimized', 'active', 'archived' ), true ) ) {
					$value = (bool) $value;
				} else {
					$value = sanitize_text_field( $value );
				}
				$note[ $field ] = $value;
			}
		}

		$numeric_fields = array(
			'x'      => 'absint',
			'y'      => 'absint',
			'width'  => 'absint',
			'height' => 'absint',
		);

		foreach ( $numeric_fields as $field => $sanitizer ) {
			if ( isset( $payload[ $field ] ) ) {
				$note[ $field ] = call_user_func( $sanitizer, $payload[ $field ] );
			}
		}

		return $note;
	}

	private function get_palette() {
		return array(
			'yellow' => '#fff7a3',
			'blue'   => '#d7ecff',
			'green'  => '#d9f7be',
			'pink'   => '#ffd6e7',
			'gray'   => '#e9ecef',
		);
	}

	private function resolve_color( $key ) {
		$palette = $this->get_palette();
		return isset( $palette[ $key ] ) ? $palette[ $key ] : $key;
	}

	private function handle_add_note() {
		$note    = $this->sanitize_note_from_request();
		$notes   = $this->get_notes();
		$notes[] = $note;
		$this->save_notes( $notes );
	}

	private function handle_edit_note() {
	if (isset( $_POST['st_admin_notes_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['st_admin_notes_nonce_field'] ) ),'st_admin_notes_nonce')) {
		$id    = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		if ( ! $id ) {
			return;
		}
		$notes = $this->get_notes();
		foreach ( $notes as $index => $note ) {
			if ( $note['id'] === $id ) {
				$notes[ $index ] = $this->merge_note_update(
					$note,
					array(
						'title'   => isset( $_POST['title'] ) ? sanitize_text_field(wp_unslash( $_POST['title'] )) : $note['title'],
						'content' => isset( $_POST['content'] ) ? sanitize_text_field(wp_unslash( $_POST['content']) ) : $note['content'],
						'color'   => isset( $_POST['color'] ) ? sanitize_text_field(wp_unslash( $_POST['color'] )) : $note['color'],
						'active'  => isset( $_POST['active'] ) ? 1 : 0,
					)
				);
				break;
			}
		}
		$this->save_notes( $notes );
	}
	}

	private function handle_delete_note() {
	if (isset( $_POST['st_admin_notes_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['st_admin_notes_nonce_field'] ) ),'st_admin_notes_nonce')) {
		$id    = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$notes = $this->get_notes();
		$notes = array_values(
			array_filter(
				$notes,
				function ( $note ) use ( $id ) {
					return isset( $note['id'] ) && $note['id'] !== $id;
				}
			)
		);
	
		$this->save_notes( $notes );
		}
	}

	private function handle_toggle_active() {
		if (isset( $_POST['st_admin_notes_nonce_field'] ) && wp_verify_nonce(sanitize_text_field( wp_unslash( $_POST['st_admin_notes_nonce_field'] ) ),'st_admin_notes_nonce')) {
		$id     = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$active = isset( $_POST['active'] ) ? (bool) $_POST['active'] : false;
		$notes  = $this->get_notes();

		foreach ( $notes as $index => $note ) {
			if ( $note['id'] === $id ) {
				$notes[ $index ]['active'] = $active;
				$notes[ $index ]['hidden'] = false;
				break;
			}
		}
		$this->save_notes( $notes );
	}
	}

	public function add_admin_bar_items( $bar ) {
		if ( ! current_user_can( $this->get_capability() ) ) {
			return;
		}

		$bar->add_node(
			array(
				'id'    => 'st-notes',
				'title' => __( 'ST Notes', 'st-admin-notes' ),
				'href'  => admin_url( 'admin.php?page=st-admin-notes' ),
			)
		);

		$bar->add_node(
			array(
				'id'     => 'st-notes-add',
				'parent' => 'st-notes',
				'title'  => __( 'Add note', 'st-admin-notes' ),
				'href'   => '#',
				'meta'   => array( 'class' => 'stn-open-modal' ),
			)
		);

		$bar->add_node(
			array(
				'id'     => 'st-notes-toggle',
				'parent' => 'st-notes',
				'title'  => __( 'Hide/Show notes', 'st-admin-notes' ),
				'href'   => '#',
				'meta'   => array( 'class' => 'stn-toggle-overlay' ),
			)
		);
	}

	public function ajax_toggle_overlay() {
		$this->verify_permissions();
		check_ajax_referer( 'st_admin_notes_nonce', 'nonce' );
		$settings                    = $this->get_settings();
		$settings['overlay_enabled'] = empty( $settings['overlay_enabled'] );
		update_option( $this->settings_key, $settings );
		wp_send_json_success(
			array(
				'overlay_enabled' => (bool) $settings['overlay_enabled'],
			)
		);
	}

}

