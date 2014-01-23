<?php
/**
*
*	Response dispatcher
*
*	@package sampa\Core\Response
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

use sampa\Exception;

final class Response {
	public $headers = array();
	public $type = 'text/html';
	public $content = '';
	public $encoding;
	public $path;
	//sets the current language
	public $language;
	private $code = 200;

	private function render_headers() {
		if (isset($this->headers['Content-Type']))
			unset($this->headers['Content-Type']);
		$response = array();
		foreach ($this->headers as $header => $value)
			$response[] = "{$header}: {$value}";
		if (strncmp($this->type, 'text/', 5) == 0)
			$response[] = "Content-Type: {$this->type}; charset={$this->encoding}";
		else
			$response[] = "Content-Type: {$this->type}";
		return $response;
	}

	private function output_headers() {
		http_response_code($this->code);
		$headers = $this->render_headers();
		foreach ($headers as $header)
			header($header);
	}

	//sets response code
	public function code($value) {
		$this->code = intval($value);
	}

	//sets multiple headers
	public function set_headers(array $headers) {
		foreach ($headers as $header => $value)
			$this->headers[$header] = $value;
	}

	//creates a HTTP redirect
	public function redirect($url = '', $permanent = false) {
		if ($url === '')
			$url = $this->path;
		else if (!preg_match('/^https?:\/\//', $url)) {
			if ((substr_compare($this->path, '/', -1, 1) == 0) && (strncmp($url, '/', 1) == 0))
				$url = substr($url, 1);
			$url = "{$this->path}{$url}";
		}
		if ($permanent)
			$this->code = 301;
		else
			$this->code = 302;
		$this->headers['Location'] = $url;
	}

	public function html($content, $type = 'text/html', $code = null) {
		$this->type = $type;
		$this->content = $content;
		if (!is_null($code))
			$this->code = $code;
	}

	public function jsonp($callback, $content = null, $type = 'application/javascript', $code = null) {
		$this->type = $type;
		if (is_null($content))
			$this->content = "{$callback}({$this->content});";
		else
			$this->content = "{$callback}(" . json_encode($content) . ');';
		if (!is_null($code))
			$this->code = $code;
	}

	public function json(array $content, $type = 'application/json', $code = null) {
		$this->type = $type;
		$this->content = json_encode($content);
		if (!is_null($code))
			$this->code = $code;
	}

	public function file($filename, $type = 'application/octet-stream', $code = null) {
		if (is_file($filename)) {
			$this->type = $type;
			$this->content = file_get_contents($filename);
			if (!is_null($code))
				$this->code = $code;
		} else
			throw new Exception\Response('File not found');
	}

	public function output() {
		if (($this->content === '') && ($this->code == 200))
			$this->code = 204;
		$this->output_headers();
		echo $this->content;
	}

	public function __construct($encoding = 'UTF-8', $path = '/', $language = 'en-us') {
		$this->encoding = $encoding;
		$this->path = $path;
		$this->language = $language;
	}

	public function __sleep() {
		return array('headers', 'type', 'content', 'encoding', 'path', 'code');
	}

}
