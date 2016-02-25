<?php
/**
*
*	Log handling
*
*	@package sampa\Core\Log
*	@copyright 2016 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*
*/

namespace sampa\Core;

use sampa\Exception;

final class Log {
	const DISABLED = 0x0001;
	const EMERGENCY = 0x0002;
	const ALERT = 0x0004;
	const CRITICAL = 0x0008;
	const ERROR = 0x0010;
	const WARNING = 0x0020;
	const NOTICE = 0x0040;
	const INFO = 0x0080;
	const DEBUG = 0x0100;
	const ALL = 0xFFFF;

	//default log level (disabled)
	private $level;
	//file handler
	private $handler = false;
	//buffered write
	private $buffered;
	private $buffer = array();

	/**
	* Interpolates context values into the message placeholders.
	*/
	private function interpolate($message, array $context = array()) {
		// build a replacement array with braces around the context keys
		$replace = array();
		foreach ($context as $key => $val)
			$replace["{{$key}}"] = $val;

		// interpolate replacement values into the message and return
		return strtr($message, $replace);
	}

	private function init_handler() {
		$dir = dirname($this->file);
		if ((!is_dir($dir)) && (!mkdir($dir, 0777, true)))
			return false;
		$this->handler = @fopen($this->file, 'a');
		return (is_resource($this->handler));
	}

	private function write($message) {
		if ($this->buffered) {
			$this->buffer[] = date('H:i:s d/m/Y ') . $message;
			return true;
		}
		if ((!is_resource($this->handler)) && (!$this->init_handler()))
			return false;
		flock($this->handler, LOCK_EX);
		$write = fwrite($this->handler, date('H:i:s d/m/Y ') . "{$message}\n");
		flock($this->handler, LOCK_UN);
		return ($write !== false);
	}

	public function __construct($file, $level = self::DISABLED, $buffered = true) {
		$this->file = $file;
		$this->level = intval($level);
		$this->buffered = $buffered;
	}

	public function __destruct() {
		if (($this->buffered) && (count($this->buffer)) && ($this->init_handler())) {
			flock($this->handler, LOCK_EX);
			fwrite($this->handler, implode("\n", $this->buffer));
			fwrite($this->handler, "\n");
			flock($this->handler, LOCK_UN);
			fclose($this->handler);
		} else if (is_resource($this->handler))
			fclose($this->handler);
	}

	public function set_level($value) {
		$this->level = intval($value);
	}

	/**
	* System is unusable.
	*
	* @param string $message
	* @param array $context
	* @return null
	*/
	public function emergency($message, array $context = array()) {
		if (($this->level & self::EMERGENCY) == self::EMERGENCY)
			$this->write('[EMERGENCY] ' . $this->interpolate($message, $context));
	}

	/**
	* Action must be taken immediately.
	*
	* Example: Entire website down, database unavailable, etc. This should
	* trigger the SMS alerts and wake you up.
	*
	* @param string $message
	* @param array $context
	* @return null
	*/
	public function alert($message, array $context = array()) {
		if (($this->level & self::ALERT) == self::ALERT)
			$this->write('[ALERT] ' . $this->interpolate($message, $context));
	}

	/**
	* Critical conditions.
	*
	* Example: Application component unavailable, unexpected Exception.
	*
	* @param string $message
	* @param array $context
	* @return null
	*/
	public function critical($message, array $context = array()) {
		if (($this->level & self::CRITICAL) == self::CRITICAL)
			$this->write('[CRITICAL] ' . $this->interpolate($message, $context));
	}

	/**
	* Runtime errors that do not require immediate action but should typically
	* be logged and monitored.
	*
	* @param string $message
	* @param array $context
	* @return null
	*/
	public function error($message, array $context = array()) {
		if (($this->level & self::ERROR) == self::ERROR)
			$this->write('[ERROR] ' . $this->interpolate($message, $context));
	}

	/**
	* Exceptional occurrences that are not errors.
	*
	* Example: Use of deprecated APIs, poor use of an API, undesirable things
	* that are not necessarily wrong.
	*
	* @param string $message
	* @param array $context
	* @return null
	*/
	public function warning($message, array $context = array()) {
		if (($this->level & self::WARNING) == self::WARNING)
			$this->write('[WARNING] ' . $this->interpolate($message, $context));
	}

	/**
	* Normal but significant events.
	*
	* @param string $message
	* @param array $context
	* @return null
	*/
	public function notice($message, array $context = array()) {
		if (($this->level & self::NOTICE) == self::NOTICE)
			$this->write('[NOTICE] ' . $this->interpolate($message, $context));
	}

	/**
	* Interesting events.
	*
	* Example: User logs in, SQL logs.
	*
	* @param string $message
	* @param array $context
	* @return null
	*/
	public function info($message, array $context = array()) {
		if (($this->level & self::INFO) == self::INFO)
			$this->write('[INFO] ' . $this->interpolate($message, $context));
	}

	/**
	* Detailed debug information.
	*
	* @param string $message
	* @param array $context
	* @return null
	*/
	public function debug($message, array $context = array()) {
		if (($this->level & self::DEBUG) == self::DEBUG)
			$this->write('[DEBUG] ' . $this->interpolate($message, $context));
	}

	/**
	* Logs with an arbitrary level.
	*
	* @param mixed $level
	* @param string $message
	* @param array $context
	* @return null
	*/
	public function log($level, $message, array $context = array()) {
		switch ($level) {
			case self::EMERGENCY:
				$this->emergency($message, $context);
				break;
			case self::ALERT:
				$this->alert($message, $context);
				break;
			case self::CRITICAL:
				$this->critical($message, $context);
				break;
			case self::ERROR:
				$this->error($message, $context);
				break;
			case self::WARNING:
				$this->warning($message, $context);
				break;
			case self::NOTICE:
				$this->notice($message, $context);
				break;
			case self::INFO:
				$this->info($message, $context);
				break;
			case self::DEBUG:
				$this->debug($message, $context);
				break;
			default:
				throw new Exception\Log("Unknown log level '{$level}'");
		}
	}
}
