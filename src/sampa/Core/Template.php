<?php
/**
*
*	Template Parser
*
*	@package sampa\Core\Template
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

use sampa\Exception;

final class Template {
	const REGEX_Block = '/<!-- BEGIN ([a-zA-Z][a-zA-Z0-9_-]*) -->(.*?)<!-- END \1 -->/sm';
	const REGEX_Comment = '/<!-- COMMENT -->.*?<!-- \/COMMENT -->\n?/sm';
	const REGEX_i18n = '/\{\{@([a-zA-Z][a-zA-Z0-9._-]*)\}\}/';
	const REGEX_i18nR = '/^\{\{@([a-zA-Z][a-zA-Z\._-]*)\}\}$/';
	const REGEX_Include = '/<!-- INCLUDE ([a-zA-Z][a-zA-Z0-9\/._-]*) -->\n?/';
	const REGEX_Placeholder = '/\{\{[a-zA-Z][a-zA-Z0-9_-]*\}\}/m';
	const REGEX_Blockholder = '/\{\{block:[a-zA-Z][a-zA-Z0-9_-]*\}\}/m';

	private $path;
	private $cache = false;
	private $base = null;
	private $title = '';
	private $charset = null;
	private $indentation = true;
	private $language = null;
	private $default_language = 'en-us';
	private $override = null;
	private $blocks = array(
		'__root__' => array(
			'data' => '',
			'inner' => array(),
			'render' => array()
		)
	);
	private $headers = array();
	private $meta = array();
	private $includes = array();
	private $i18n = array();
	private $environment = array();
	private $js = array();

	/*
	*
	*	CLASS CONSTRUCTOR/DESTRUCTOR
	*
	*/

	public function __construct($path = './', &$cache = false, $base = null, $language = null) {
		if (substr_compare($path, '/', -1, 1) == 1)
			$path .= '/';
		$this->path = $path;
		$this->cache = $cache;
		$this->base = $base;
		$this->language = $language;
		$this->meta = array(
			'robots' => 'index,follow',
			'generator' => 'http://github.com/appdeck/sampa'
		);
	}

	/*
	*
	*	USER FUNCTIONS
	*
	*/

	public function set_title($value) {
		$this->title = $value;
	}

	public function set_language($value) {
		$this->language = $value;
	}

	public function set_environment($name, $value) {
		if ($value === '')
			unset($this->environment[$name]);
		else
			$this->environment[$name] = $value;
	}

	public function render_headers($output = false) {
		if (!$output)
			return $this->headers;
		foreach ($this->headers as $header => $value)
			header("{$header}: {$value}");
	}

	public function render_body() {
		$this->build();
		$this->parse_cleanup();
		if ($this->indentation)
			$bfr = $this->indent($this->blocks['__root__']['data'], 2);
		else
			$bfr = $this->blocks['__root__']['data'];
		if (isset($this->includes['js']['body']))
			foreach ($this->includes['js']['body'] as $src)
				$bfr .= "		<script type=\"text/javascript\" src=\"{$src}\"></script>\n";
		if (count($this->js)) {
			$bfr .= "		<script type=\"text/javascript\">\n";
			foreach ($this->js as $js)
				$bfr .= $this->indent($js, 3);
			$bfr .= "		</script>\n";
		}
		return $bfr;
	}

	public function render_html($display = false, $display_media = 'all') {
		$bfr = "<!DOCTYPE html>\n";
		if (is_null($this->language))
			$bfr .= "<html lang=\"en-us\"";
		else {
			$bfr .= '<html lang="';
			$bfr .= htmlentities($this->language, ENT_QUOTES, $this->charset);
			$bfr .= '"';
		}
		if ((!is_null($this->override)) && (isset($this->override['html'])))
			$bfr .= " {$this->override['html']}";
		$bfr .= ">\n";
		$bfr .= "	<head>\n";
		if (is_null($this->charset))
			$bfr .= "		<meta charset=\"UTF-8\">\n";
		else
			$bfr .= "		<meta charset=\"{$this->charset}\">\n";
		if (isset($this->meta['title'])) {
			$this->meta['title'] = str_replace('{title}', $this->title, $this->meta['title']);
			$this->title = htmlentities($this->meta['title'], ENT_QUOTES, $this->charset);
			unset($this->meta['title']);
		}
		$this->build_i18n($this->title);
		$bfr .= "		<title>{$this->title}</title>\n";
		if (isset($this->meta['favicon'])) {
			$favicon = $this->meta['favicon'];
			unset($this->meta['favicon']);
		}
		foreach ($this->meta as $key => $value) {
			$this->build_i18n($value);
			$bfr .= "		<meta name=\"{$key}\" content=\"{$value}\">\n";
		}
		if (!empty($this->base))
			$bfr .= "		<base href=\"{$this->base}\">\n";
		if (isset($favicon))
			$bfr .= "		<link rel=\"icon\" type=\"image/x-icon\" href=\"{$favicon}\">\n";
		if (isset($this->includes['css']))
			foreach ($this->includes['css'] as $src => $media)
				if (($media === $display_media) || ($display_media === 'all'))
					$bfr .= "		<link rel=\"stylesheet\" type=\"text/css\" href=\"{$src}\" media=\"{$media}\">\n";
		if (isset($this->includes['js']['head']))
			foreach ($this->includes['js']['head'] as $src)
				$bfr .= "		<script type=\"text/javascript\" src=\"{$src}\"></script>\n";
		if ((!is_null($this->override)) && (isset($this->override['head']))) {
			if ($this->indentation)
				$bfr .= $this->indent($this->override['head'], 2) . "\n";
			else
				$bfr .= "{$this->override['head']}\n";
		}
		$bfr .= "	</head>\n";
		$bfr .= '	<body';
		if ((!is_null($this->override)) && (isset($this->override['body'])))
			$bfr .= " {$this->override['body']}";
		$bfr .= ">\n";
		$bfr .= $this->render_body();
		$bfr .= "	</body>\n";
		$bfr .= "</html>\n";
		if ($display)
			echo $bfr;
		else
			return $bfr;
	}

	public function load($file, $placeholder = '') {
		$file = "{$this->path}{$file}";
		if (is_file($file)) {
			$data = $this->parse_file($file);
			if (empty($placeholder))
				$this->blocks['__root__']['data'] = $data['body'];
			else
				$this->set_var($placeholder, trim($data['body']), false);
		} else
			throw new Exception\Template("File not found: '{$file}'");
	}

	public function set_var($placeholder, $value, $escape = true) {
		if ($escape)
			$value = htmlentities($value, ENT_QUOTES, $this->charset);
		$pos = strrpos($placeholder, '.');
		if ($pos === false) {
			$this->blocks['__root__']['data'] = str_replace("{{{$placeholder}}}", $value, $this->blocks['__root__']['data']);
			foreach ($this->js as &$js)
				$js = str_replace("{{{$placeholder}}}", $value, $js);
		} else {
			$blockname = substr($placeholder, 0, $pos);
			$placeholder = substr($placeholder, ($pos + 1));
			$block = $this->find_block($blockname);
			if ($block === false)
				throw new Exception\Template("Block not found '{$blockname}'");
			foreach ($block['ptr']['render'] as &$data)
				$data = str_replace("{{{$placeholder}}}", $value, $data);
		}
	}

	public function block_show($blockname) {
		$block = $this->find_block($blockname);
		if ($block === false)
			throw new Exception\Template("Block not found '{$blockname}'");
		unset($block['ptr']['hide']);
		if (!count($block['ptr']['render']))
			$block['ptr']['render'][] = $block['ptr']['data'];
	}

	public function block_hide($blockname) {
		$block = $this->find_block($blockname);
		if ($block === false)
			throw new Exception\Template("Block not found '{$blockname}'");
		$block['ptr']['hide'] = true;
	}

	public function block_parse($blockname, array $content = array(), $escape = true) {
		$block = $this->find_block($blockname);
		if ($block === false)
			throw new Exception\Template("Block not found '{$blockname}'");
		$src = $block['ptr']['data'];

		$tmp = $this->build_blocks($block['ptr']['inner']);
		if (count($tmp))
			foreach ($tmp as $name => $inner)
				$src = str_replace("{{block:{$name}}}", trim($inner), $src);
		foreach ($block['ptr']['inner'] as &$data)
			$data['render'] = array();

		foreach ($content as $placeholder => $value) {
			if ($escape)
				$value = htmlentities($value, ENT_QUOTES, $this->charset);
			$src = str_replace("{{{$placeholder}}}", $value, $src);
		}
		$this->clean_placeholders($src);
		$block['ptr']['render'][] = $src;
	}

	public function block_mparse($blockname, array $contents = array(), $escape = true) {
		foreach ($contents as $content)
			$this->block_parse($blockname, $content, $escape);
	}

	/*
	*
	*	CLASS PROPERTIES
	*
	*/

	public function get_meta() {
		return $this->meta;
	}

	public function get_includes() {
		return $this->includes;
	}

	public function get_js() {
		return $this->js;
	}

	/*
	*
	*	CLASS FUNCTIONS (PARSING)
	*
	*/

	private function parse_file($file) {
		$data = $this->get_cache($file);
		if (is_null($data)) {
			$extension = substr($file, (strrpos($file, '.') + 1));
			switch ($extension) {
				case 'html':
					$data = $this->read_html($file);
					break;
				case 'js':
					$data = $this->read_js($file);
					break;
				case 'xml':
					$data = $this->read_xml($file);
					break;
				default:
					throw new Exception\Template("Invalid file type '{$extension}'");
			}
			$bodyblocks = array();
			$this->parse_comments($data['body']);
			$this->parse_blocks($data['body'], $bodyblocks);
			$jsblocks = array();
			$this->parse_comments($data['js']);
			$this->parse_blocks($data['js'], $jsblocks);
			$data['blocks'] = array_merge($bodyblocks, $jsblocks);
			//must add $data to cache at this point
			$this->set_cache($file, $data);
		}
		$this->parse_includes($data, $file);
		$this->update_properties($data['properties']);
		$this->update_headers($data['headers']);
		$this->update_meta($data['meta']);
		$this->update_includes($data['includes']);
		$this->update_i18n($data['i18n']);
		$this->update_blocks($data['blocks']);
		$this->update_js($data['js']);
		return $data;
	}

	private function parse_comments(&$var) {
		$var = preg_replace(self::REGEX_Comment, '', $var);
	}

	private function parse_blocks(&$var, &$blocks) {
		$blocks = array();
		if (preg_match_all(self::REGEX_Block, $var, $matches)) {
			foreach ($matches[1] as $id => $block) {
				$blocks[$block] = array(
					'data' => $matches[2][$id],
					'inner' => array(),
					'render' => array()
				);
				$var = str_replace($matches[0][$id], "{{block:{$block}}}", $var);
			}
			foreach ($blocks as &$block)
				$this->parse_blocks($block['data'], $block['inner']);
		}
	}

	private function parse_includes(&$var, $parent) {
		foreach (array('body', 'js') as $item)
			if (preg_match_all(self::REGEX_Include, $var[$item], $matches))
				foreach ($matches[1] as $id => $file) {
					if ($parent !== $file) {
						$file = "{$this->path}{$file}";
						if (is_file($file)) {
							$data = $this->parse_file($file);
							if (!empty($data[$item]))
								$var = str_replace($matches[0][$id], trim($data[$item]), $var);
						} else
							throw new Exception\Template("File not found '{$file}'");
					} else
						throw new Exception\Template("Include recursion '{$file}'");
				}
	}

	/*
	*
	*	CLASS FUNCTIONS (BUILDING)
	*
	*/

	private function build() {
		$blocks = $this->build_blocks($this->blocks['__root__']['inner']);
		foreach ($blocks as $placeholder => $content) {
			$this->blocks['__root__']['data'] = str_replace("{{block:{$placeholder}}}", $content, $this->blocks['__root__']['data']);
			foreach ($this->js as &$js)
				$js = str_replace("{{block:{$placeholder}}}", $content, $js);
		}
		$this->build_i18n($this->blocks['__root__']['data']);
		foreach ($this->js as &$js)
			$this->build_i18n($js);
		$this->build_environment();
	}

	private function build_blocks($blocks) {
		$ret = array();
		foreach ($blocks as $name => $block) {
			if (empty($block['hide'])) {
				if (count($block['inner'])) {
					$src = '';
					if (count($block['render']))
						$src = implode("\n", $block['render']);
					$tmp = $this->build_blocks($block['inner']);
					if (count($tmp)) {
						if ($src === '')
							$src = $block['data'];
						foreach ($tmp as $placeholder => $content)
							$src = str_replace("{{block:{$placeholder}}}", $content, $src);
					}
					if (!empty($src))
						$ret[$name] = $src;
				} else if (count($block['render']))
					$ret[$name] = implode("\n", $block['render']);
			}
		}
		return $ret;
	}

	private function build_i18n(&$var) {
		if (preg_match_all(self::REGEX_i18n, $var, $matches))
			foreach ($matches[1] as $id) {
				$i18n = $this->find_i18n($id);
				if ($i18n == false)
					throw new Exception\Template("i18n string not found ({$id})");
				else
					$var = str_replace("{{@{$id}}}", htmlentities($i18n, ENT_QUOTES, $this->charset), $var);
			}
	}

	private function build_environment() {
		foreach ($this->environment as $placeholder => $value)
			$this->set_var("#{$placeholder}", $value);
	}

	private function indent($content, $indent = 0) {
		$content = str_replace("\r", '', $content);
		$tmp = explode("\n", $content);
		$count = count($tmp);
		$bfr = '';
		for ($i = 0; $i < $count; $i++) {
			$tmp[$i] = trim($tmp[$i]);
			if (!empty($tmp[$i])) {
				if (preg_match('/^<[^>]+\/><[^>]+>$/', $tmp[$i])) {
					$bfr .= str_repeat("\t", $indent) . $tmp[$i] . "\n";
					$indent++;
				} else if ((preg_match('/^<[^>]+\/>$/', $tmp[$i])) || (preg_match('/<[^>]+>.*?<\/[^>]+>$/', $tmp[$i])) || (preg_match('/^<!--.*?-->/', $tmp[$i])))
					$bfr .= str_repeat("\t", $indent) . $tmp[$i] . "\n";
				else if (preg_match('/^\}[^\{]+\{$/', $tmp[$i]))
					$bfr .= str_repeat("\t", ($indent - 1)) . $tmp[$i] . "\n";
				else if ((preg_match('/^<\/[^>]+>$/', $tmp[$i])) || (preg_match('/^\}/', $tmp[$i]))) {
					$indent--;
					$bfr .= str_repeat("\t", $indent) . $tmp[$i] . "\n";
				} else if ((preg_match('/^<[^>]+>$/', $tmp[$i])) || (preg_match('/\{$/', $tmp[$i]))) {
					$bfr .= str_repeat("\t", $indent) . $tmp[$i] . "\n";
					$indent++;
				} else
					$bfr .= str_repeat("\t", $indent) . $tmp[$i] . "\n";
			}
		}
		return $bfr;
	}

	/*
	*
	*	CLASS FUNCTIONS (CLEANING)
	*
	*/

	private function parse_cleanup() {
		$this->clean_placeholders($this->blocks['__root__']['data']);
		$this->clean_blockholders($this->blocks['__root__']['data']);
		$this->clean_blankspaces($this->blocks['__root__']['data']);
		foreach ($this->js as &$js) {
			$this->clean_placeholders($js);
			$this->clean_blockholders($js);
			$this->clean_blankspaces($js);
		}
	}

	private function clean_placeholders(&$var) {
		$var = preg_replace(self::REGEX_Placeholder, '', $var);
	}

	private function clean_blockholders(&$var) {
		$var = preg_replace(self::REGEX_Blockholder, '', $var);
	}

	private function clean_blankspaces(&$var) {
		$var = preg_replace("/\r+/m", '', $var);
		$var = preg_replace("/\n+/m", "\n", $var);
		$var = preg_replace("/\n\t+\n+/m", "\n", $var);
	}

	/*
	*
	*	CLASS FUNCTIONS (CACHING)
	*
	*/

	private function get_cache($file) {
		if ($this->cache === false)
			return null;
		$id = sha1($file);
		if ($this->cache->has($id)) {
			$cache = $this->cache->get($id);
			foreach ($cache['time'] as $dependency => $mtime)
				if (filemtime($dependency) != $mtime)
					return null;
			return $cache['data'];
		}
		return null;
	}

	private function set_cache($file, array $data) {
		if ($this->cache === false)
			return;
		$id = sha1($file);
		$cache = array(
			'time' => array(
				$file => filemtime($file)
			),
			'data' => $data
		);
		foreach ($data['dependency'] as $dependency)
			$cache['time'][$dependency] = filemtime($dependency);
		$this->cache->set($id, $cache);
	}

	/*
	*
	*	CLASS FUNCTIONS (AUXILIAR)
	*
	*/

	private function update_properties($data) {
		if (((is_null($this->charset)) || ($this->charset === '')) && (!empty($data['charset'])))
			$this->charset = $data['charset'];
		if (($this->indentation) && (isset($data['indentation'])))
			$this->indentation = ($data['indentation'] === 'false' ? false : true);
		if (count($data['override'])) {
			if (is_null($this->override))
				$this->override = $data['override'];
			else
				$this->override = array_merge($this->override, $data['override']);
		}
	}

	private function update_headers($data) {
		foreach ($data as $header) {
			if (empty($header['override']))
				$header['override'] = 'no';
			switch ($header['override']) {
				case 'yes':
					$this->headers[$header['name']] = $header['value'];
					break;
				case 'no':
					if (empty($this->headers[$header['name']]))
						$this->headers[$header['name']] = $header['value'];
			}
		}
	}

	private function update_meta($data) {
		foreach ($data as $type => &$info) {
			if (empty($info['override']))
				$info['override'] = 'no';
			switch ($info['override']) {
				case 'yes':
					$this->meta[$type] = $info['value'];
					break;
				case 'no':
					if (empty($this->meta[$type]))
						$this->meta[$type] = $info['value'];
					break;
				case 'append':
					if (empty($this->meta[$type]))
						$this->meta[$type] = $info['value'];
					else if ($type === 'keywords')
						$this->meta[$type] = "{$this->meta[$type]}, {$info['value']}";
					else
						$this->meta[$type] = "{$this->meta[$type]} {$info['value']}";
					break;
				case 'prepend':
					if (empty($this->meta[$type]))
						$this->meta[$type] = $info['value'];
					else if ($type === 'keywords')
						$this->meta[$type] = "{$info['value']}, {$this->meta[$type]}";
					else
						$this->meta[$type] = "{$info['value']} {$this->meta[$type]}";
					break;
				case 'placeholder':
					$this->meta[$type] = str_replace("{{$type}}", $info['value'], $this->meta[$type]);
			}
		}
	}

	private function update_includes($data) {
		if (count($this->includes) == 0)
			$this->includes = $data;
		else {
			if (isset($data['css']))
				foreach ($data['css'] as $file => $media)
					if (empty($this->includes['css'][$file]))
						$this->includes['css'][$file] = $media;
			if (isset($data['js']))
				foreach ($data['js'] as $type => $files)
					foreach ($files as $file)
						if (((empty($this->includes['js']['head'])) || (!in_array($file, $this->includes['js']['head']))) &&
							((empty($this->includes['js']['body'])) || (!in_array($file, $this->includes['js']['body']))))
							$this->includes['js'][$type][] = $file;
		}
	}

	private function update_i18n($data) {
		$this->i18n = array_replace_recursive($this->i18n, $data);
	}

	private function update_blocks($data) {
		$this->blocks['__root__']['inner'] = array_merge($this->blocks['__root__']['inner'], $data);
	}

	private function update_js($data) {
		if (!empty($data))
			$this->js[] = $data;
	}

	private function read_html($file) {
		$ret = array(
			'dependency' => array(),
			'properties' => array(
				'charset' => '',
				'indentation' => '',
				'override' => array()
			),
			'headers' => array(),
			'meta' => array(),
			'includes' => array(
				'css' => array(),
				'js' => array()
			),
			'i18n' => array(),
			'blocks' => array(),
			'body' => '',
			'js' => ''
		);
		$ret['body'] = trim(file_get_contents($file));
		$import = substr($file, 0, (strlen($file) - 4)) . 'lang';
		if (is_file($import)) {
			$ret['dependency'][] = $import;
			$ret['i18n'] = $this->load_i18n($import);
		}
		$import = substr($file, 0, (strlen($file) - 4)) . 'js';
		if (is_file($import)) {
			$ret['dependency'][] = $import;
			$ret['js'] = trim(file_get_contents($import));
		}
		return $ret;
	}

	private function read_js($file) {
		$ret = array(
			'dependency' => array(),
			'properties' => array(
				'charset' => '',
				'indentation' => '',
				'override' => array()
			),
			'headers' => array(),
			'meta' => array(),
			'includes' => array(
				'css' => array(),
				'js' => array()
			),
			'i18n' => array(),
			'blocks' => array(),
			'body' => '',
			'js' => ''
		);
		$ret['js'] = trim(file_get_contents($file));
		$import = substr($file, 0, (strlen($file) - 2)) . 'lang';
		if (is_file($import)) {
			$ret['dependency'][] = $import;
			$ret['i18n'] = $this->load_i18n($import);
		}
		return $ret;
	}

	private function read_xml($file) {
		$ret = array(
			'dependency' => array(),
			'properties' => array(
				'charset' => '',
				'indentation' => '',
				'override' => array()
			),
			'headers' => array(),
			'meta' => array(),
			'includes' => array(
				'css' => array(),
				'js' => array()
			),
			'i18n' => array(),
			'blocks' => array(),
			'body' => '',
			'js' => ''
		);
		$xml = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_NOBLANKS | LIBXML_NOCDATA);
		if ($xml === false)
			throw new Exception\Template("Invalid XML file '{$file}'");
		if (isset($xml['charset']))
			$ret['properties']['charset'] = (string)$xml['charset'];
		if (isset($xml['indentation']))
			$ret['properties']['indentation'] = (string)$xml['indentation'];
		if (isset($xml['language']))
			$this->default_language = (string)$xml['language'];
		if (isset($xml->override)) {
			if (isset($xml->override->html))
				$ret['properties']['override']['html'] = trim((string)$xml->override->html);
			if (isset($xml->override->head))
				$ret['properties']['override']['head'] = trim((string)$xml->override->head);
			if (isset($xml->override->body))
				$ret['properties']['override']['body'] = trim((string)$xml->override->body);
		}
		if (isset($xml->headers))
			foreach ($xml->headers->children() as $data) {
				$tmp = array();
				foreach ($data->attributes() as $key => $value)
					$tmp[(string)$key] = (string)$value;
				 $ret['headers'][] = $tmp;
			}
		if (isset($xml->meta))
			foreach ($xml->meta->children() as $name => $data)
				foreach ($data->attributes() as $key => $value)
					$ret['meta'][(string)$name][(string)$key] = (string)$value;
		if (isset($xml->includes))
			foreach ($xml->includes->children() as $name => $data) {
				if (isset($data['src'])) {
					switch ((string)$name) {
						case 'css':
							if (isset($data['media']))
								$ret['includes']['css'][(string)$data['src']] = (string)$data['media'];
							else
								$ret['includes']['css'][(string)$data['src']] = 'all';
							break;
						case 'js':
							if (isset($data['place']))
								switch ((string)$data['place']) {
									case 'head':
										$ret['includes']['js']['head'][] = (string)$data['src'];
										break;
									case 'body':
										$ret['includes']['js']['body'][] = (string)$data['src'];
										break;
								}
							else
								$ret['includes']['js']['body'][] = (string)$data['src'];
					}
				}
			}
		if (isset($xml->i18n)) {
			if (isset($xml->i18n['import'])) {
				$import = "{$this->path}{$xml->i18n['import']}";
				if (is_file($import)) {
					$ret['dependency'][] = $import;
					$ret['i18n'] = $this->load_i18n($import);
				} else
					throw new Exception\Template("File not found '{$import}'");
			} else
				$ret['i18n'] = $this->load_i18n($xml->i18n);
		} else {
			$import = substr($file, 0, (strlen($file) - 3)) . 'lang';
			if (is_file($import)) {
				$ret['dependency'][] = $import;
				$ret['i18n'] = $this->load_i18n($import);
			}
		}
		if (isset($xml->body)) {
			if (isset($xml->body['import'])) {
				$import = "{$this->path}{$xml->body['import']}";
				if (is_file($import)) {
					$ret['dependency'][] = $import;
					$ret['body'] = trim(file_get_contents($import));
				} else
					throw new Exception\Template("File not found '{$import}'");
			} else
				$ret['body'] = trim((string)$xml->body);
		} else {
			$import = substr($file, 0, strrpos($file, '.')) . '.html';
			if (is_file($import)) {
				$ret['dependency'][] = $import;
				$ret['body'] = trim(file_get_contents($import));
			}
		}
		if (isset($xml->js)) {
			if (isset($xml->js['import'])) {
				$import = "{$this->path}{$xml->js['import']}";
				if (is_file($import)) {
					$ret['dependency'][] = $import;
					$ret['js'] = trim(file_get_contents($import));
				} else
					throw new Exception\Template("File not found '{$import}'");
			} else
				$ret['js'] = trim((string)$xml->js);
		} else {
			$import = substr($file, 0, strrpos($file, '.')) . '.js';
			if (is_file($import)) {
				$ret['dependency'][] = $import;
				$ret['js'] = trim(file_get_contents($import));
			}
		}
		return $ret;
	}

	private function load_i18n($file) {
		$data = file_get_contents($file);
		if ($data === false)
			return array();
		$json = json_decode($data, true);
		if (is_null($json))
			return array();
		return $json;
	}

	private function find_block($blockname, &$blocks = null) {
		if (is_null($blocks))
			$blocks = &$this->blocks['__root__']['inner'];
		$pos = strpos($blockname, '.');
		if ($pos === false) {
			if (isset($blocks[$blockname]))
				return array(
					'ptr' => &$blocks[$blockname]
				);
			return false;
		}
		$name = substr($blockname, 0, $pos);
		if (isset($blocks[$name]))
			return $this->find_block(substr($blockname, ($pos + 1)), $blocks[$name]['inner']);
		return false;
	}

	private function find_i18n($path, &$i18n = null) {
		if (is_null($i18n))
			$i18n = &$this->i18n;
		if (count($i18n) == 0)
			return false;
		$pos = strpos($path, '.');
		if ($pos === false) {
			if (isset($i18n[$path])) {
				if (isset($i18n[$path][$this->language]))
					return $i18n[$path][$this->language];
				else if (isset($i18n[$path][$this->default_language]))
					return $i18n[$path][$this->default_language];
				else if (is_string($i18n[$path])) {
					if (preg_match(self::REGEX_i18nR, $i18n[$path], $matches))
						return $this->find_i18n($matches[1]);
					return $i18n[$path];
				}
				return false;
			} else if ((is_string($i18n)) && (preg_match(self::REGEX_i18nR, $i18n, $matches)))
				return $this->find_i18n("{$matches[1]}.{$path}");
			return false;
		}
		$name = substr($path, 0, $pos);
		if (isset($i18n[$name]))
			return $this->find_i18n(substr($path, ($pos + 1)), $i18n[$name]);
		return false;
	}
}
