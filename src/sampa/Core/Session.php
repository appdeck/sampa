<?php
/**
*
*	Session manipulation
*
*	@package sampa\Core\Session
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

final class Session {
	//holds class instance for singleton
	private static $instance = null;
	//holds max idletime before timeout of session
	private $idletime;
	//holds session save path
	private $path = '';
	//holds secure class instance for crypto functions
	private $secure = null;
	//holds the key used in crypto functions
	private $key = '';
	//holds the flash message status
	private $flash = false;

	//session handler close function
	public function s_close() {
		return true;
	}

	//session handler destroy function
	public function s_destroy($id) {
		$file = "{$this->path}/sess_{$id}";
		if (is_file($file))
			unlink($file);
		return true;
	}

	//session handler garbage collect function
	public function s_gc($maxlifetime) {
		$dir = dir($this->path);
		foreach ($dir as $file)
			if (((filemtime($file) + $maxlifetime) < time()) && (is_file($file)))
				unlink($file);
		return true;
	}

	//session handler open function
	public function s_open($path, $name) {
		if ($path === '')
			$this->path = sys_get_temp_dir();
		else
			$this->path = $path;
		if (!is_dir($this->path))
			mkdir($this->path, 0777);
		return true;
	}

	//session handler read function
	public function s_read($id) {
		$file = "{$this->path}/sess_{$id}";
		if (is_file($file)) {
			$data = file_get_contents($file);
			return $this->secure->decrypt_3des($this->key, $data);
		}
		return '';
	}

	//session handler write function
	public function s_write($id, $data) {
		return file_put_contents("{$this->path}/sess_{$id}", $this->secure->encrypt_3des($this->key, $data), LOCK_EX);
	}

	//class constructor
	public function __construct(&$config) {
		if ($config->read('framework/session/secure', true)) {
			$this->secure = new Secure;
			$this->key = $this->secure->sha512($config->read('framework/session/secure_key', 'sampa-framework'));

			session_set_save_handler(
				array($this, 's_open'),
				array($this, 's_close'),
				array($this, 's_read'),
				array($this, 's_write'),
				array($this, 's_destroy'),
				array($this, 's_gc')
			);

			//the following prevents unexpected effects when using objects as save handlers
			register_shutdown_function('session_write_close');
		}
		session_name($config->read('framework/session/name', 'sampa'));
		$this->idletime = $config->read('framework/sesion/idletime', 0);
		$domain = $config->read('framework/app/domain', $_SERVER['HTTP_HOST']);
		if ($config->read('framework/session/subdomain', false))
			$domain = ".{$domain}";
		session_set_cookie_params($this->idletime, $config->read('framework/app/web_path', '/'), $domain, $config->read('framework/session/ssl', false));
		session_start();
		if ((empty($_SESSION['__sid'])) || ($_SESSION['__sid'] !== session_id())) {
			$this->destroy();
			session_start();
			$this->regenerate();
		}
		$_SESSION['__timeout'] = time() + $this->idletime;
		if (isset($_SESSION['__flash']))
			$this->flash = true;
	}

	//class destructor
	public function __destruct() {
		if ($this->flash)
			unset($_SESSION['__flash']);
		session_write_close();
	}

	//singleton method - avoids the creation of more than one instance
	public static function singleton(&$config) {
		//checks if there is an instance of class, if not, create it
		if ((is_null(self::$instance)) || (!(self::$instance instanceof Session)))
			self::$instance = new Session($config);
		return self::$instance;
	}

	//regenerates session id
	public function regenerate() {
		session_regenerate_id(true);
		$_SESSION['__sid'] = session_id();
	}

	//destroys entire session information
	public function destroy() {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', (time() - 42000), $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		session_unset();
		session_destroy();
		$_SESSION = array();
	}

	//returns session id
	public function id() {
		return session_id();
	}

	//cleans session information
	public function clean() {
		$_SESSION = array();
	}

	//checks session timeout
	public function check_timeout() {
		if (($this->idletime) && (isset($_SESSION['__timeout'])))
			return ($_SESSION['__timeout'] < time());
		return false;
	}

	public function gen_token() {
		$token = sha1(uniqid(rand(), true) . microtime(true) . session_id());
		$_SESSION['__token'] = $token;
		return $token;
	}

	public function check_token($value) {
		if ((isset($_SESSION['__token'])) && ($_SESSION['__token'] === $value))
			return true;
		return false;
	}

	public function set_flash($index, $value) {
		$_SESSION['__flash'][$index] = $value;
		$this->flash = false;
	}

	public function get_flash($index = null) {
		if (is_null($index)) {
			if (isset($_SESSION['__flash']))
				return $_SESSION['__flash'];
		} else {
			if (isset($_SESSION['__flash'][$index]))
				return $_SESSION['__flash'][$index];
		}
		return false;
	}

	public function keep_flash() {
		$this->flash = false;
	}

	//gets session value
	public function __get($index) {
		if (isset($_SESSION[$index]))
			return $_SESSION[$index];
		return null;
	}

	//sets session value
	public function __set($index, $value) {
		if ($value === '')
			unset($_SESSION[$index]);
		else
			$_SESSION[$index] = $value;
	}

	//checks if session item is set
	public function __isset($index) {
		return isset($_SESSION[$index]);
	}

	//unset session value
	public function __unset($index) {
		unset($_SESSION[$index]);
	}

}
