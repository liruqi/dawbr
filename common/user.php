<?php

menu_register(array(
  'oauth' => array(
    'callback' => 'user_oauth',
    'hidden' => 'true',
  ),
));

function user_oauth() {
    if (!isset($_GET['code'])) {
        var_dump(debug_backtrace());
        $_SESSION = NULL;
        exit;
    }

	// Flag forces twitter_process() to use OAuth signing
	$GLOBALS['user']['type'] = 'oauth';

    $o = new SaeTOAuthV2(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET);
    // Generate ACCESS token request
    $last_key = $o->getAccessToken('code',
       array('code'=>$_GET['code'], 'redirect_uri'=>BASE_URL.'oauth') 
    );
    if (! $last_key) {
        var_dump(debug_backtrace());
        $_SESSION = NULL;
        exit;
    }
    // file_put_contents("/tmp/dabrlog", "getAccessToken: " . json_encode($last_key)."var {$_REQUEST['oauth_verifier']} \n", FILE_APPEND);
    // Store ACCESS tokens in COOKIE

    $_SESSION['token'] = $last_key;
    setcookie( 'weibojs_'.$o->client_id, http_build_query($last_key) );

    $c = new SaeTClientV2( OAUTH_CONSUMER_KEY , OAUTH_CONSUMER_SECRET , $_SESSION['token']['access_token'] );
    $uid_get = $c->get_uid();
    $uid = $uid_get['uid'];
    $user = $c->show_user_by_id( $uid);//根据ID获取用户等基本信息
	if (empty($user["id"])) {
	var_dump(debug_backtrace());
exit;
	}
       $_SESSION['user']['username'] = $user["id"];
       $_SESSION['user']['screen_name'] = $user["screen_name"];
    #echo '<html>Welcome'.$user["screen_name"].',<a href="/">Home</a><br /><a href="/sdk/weibolist.php">Test</a></html>';
header("Location: ".BASE_URL);
    exit;
}

function user_ensure_authenticated() {
	if (!user_is_authenticated()) {
		$content = theme('login');
		$content .= file_get_contents('about.html');
		theme('page', 'Login', $content);
	}
}

function user_logout() {
	unset($GLOBALS['user']);
    $_SESSION = array();
	setcookie('USER_AUTH', '', time() - 3600, '/');
}

function user_is_authenticated() {
  if (!($GLOBALS['user']['screen_name'])) {
      $GLOBALS['user'] = $_SESSION['user'];
  }
  if (!$GLOBALS['user']['screen_name']) {
      return false;
  }
  return true;
}

function user_current_username() {
  return $GLOBALS['user']['username'];
}

function user_is_current_user($username) {
  return (strcasecmp($username, user_current_username()) == 0);
}

function user_type() {
  return $GLOBALS['user']['type'];
}

function _user_save_cookie($stay_logged_in = 0) {
  $cookie = _user_encrypt_cookie();
  $duration = 0;
  if ($stay_logged_in) {
    $duration = time() + (3600 * 24 * 365);
  }
  setcookie('USER_AUTH', $cookie, $duration, '/');
}

function _user_encryption_key() {
  return ENCRYPTION_KEY;
}

function _user_encrypt_cookie() {
  $plain_text = $GLOBALS['user']['username'] . ':' . $GLOBALS['user']['password'] . ':' . $GLOBALS['user']['type'] . ':' . $GLOBALS['user']['screen_name'];
  
  $td = mcrypt_module_open('blowfish', '', 'cfb', '');
  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
  mcrypt_generic_init($td, _user_encryption_key(), $iv);
  $crypt_text = mcrypt_generic($td, $plain_text);
  mcrypt_generic_deinit($td);
  return base64_encode($iv.$crypt_text);
}
  
function _user_decrypt_cookie($crypt_text) {
  $crypt_text = base64_decode($crypt_text);
  $td = mcrypt_module_open('blowfish', '', 'cfb', '');
  $ivsize = mcrypt_enc_get_iv_size($td);
  $iv = substr($crypt_text, 0, $ivsize);
  $crypt_text = substr($crypt_text, $ivsize);
  mcrypt_generic_init($td, _user_encryption_key(), $iv);
  $plain_text = mdecrypt_generic($td, $crypt_text);
  mcrypt_generic_deinit($td);
  
  list($GLOBALS['user']['username'], $GLOBALS['user']['password'], $GLOBALS['user']['type'], $GLOBALS['user']['screen_name']) = explode(':', $plain_text);
}

function theme_login() {
    // Generate AUTH token request
    $oauth = new SaeTOAuthV2(OAUTH_CONSUMER_KEY,OAUTH_CONSUMER_SECRET);

    // redirect user to authorisation URL
    $authorise_url = $oauth->getAuthorizeURL( BASE_URL.'oauth');

    $_SESSION['keys'] = $token;
    // file_put_contents("/tmp/dabrlog", "token:" . json_encode($token)." $authorise_url \n", FILE_APPEND);
    return '
<p><strong><a href="' . $authorise_url . '">Sign in with Sina/OAuth</a></strong><br />
</p>
  
';
}

function theme_logged_out() {
  return '<p>Logged out. <a href="">Login again</a></p>';
}

?>
