<?php
namespace Opencart\Admin\Model\Extension\ClickerVqmodManager\Module;
class Vqmod extends \Opencart\System\Engine\Model {
	public $vqmod_dir = 'vqmod';
	public $vqmod_url = 'vqmod';
	public $vqmod_log_separator = '----------------------------------------------------------------------';

	public function __construct($registry) {
		parent::__construct($registry);

		$this->vqmod_log_separator = str_repeat('-', 70);

		// Get default VQMod dir
		$this->vqmod_url = HTTP_CATALOG . $this->vqmod_url . '/';
		$this->vqmod_dir = DIR_OPENCART . $this->vqmod_dir . '/';

		// Get installed VQMod dir
		$classes = get_declared_classes();
		if (in_array('VQMod', $classes)) {
			$rc = new \ReflectionClass('VQMod');
			$file = $rc->getFileName();
			if ($file) {
				$pathinfo = pathinfo($file);
				$this->vqmod_dir = realpath($pathinfo['dirname']) . '/';
			}
		}
	}

	public function getVQModDir() {
		return $this->vqmod_dir;
	}

	public function getVQModUrl() {
		return $this->vqmod_url;
	}

	public function addVQMod($data) {
		$filename = $data['filename'];

		if (!empty($data['status'])) {
			$filename .= '.xml';
		} else {
			$filename .= '.xml_';
		}

		$filename = $this->vqmod_dir . 'xml/' . $filename;

		file_put_contents($filename, $data['xml']);

		$vqmod_id = bin2hex(realpath($filename));

		return $vqmod_id;
	}

	public function editVQMod($vqmod_id, $data) {
		$filename_old = hex2bin($vqmod_id);
		$filename = $data['filename'];

		if (!empty($data['status'])) {
			$filename .= '.xml';
		} else {
			$filename .= '.xml_';
		}

		$filename = $this->vqmod_dir . 'xml/' . $filename;

		file_put_contents($filename_old, $data['xml']);

		if ($filename_old != $filename) {
			rename($filename_old, $filename);
		}

		$vqmod_id = bin2hex(realpath($filename));

		return $vqmod_id;
	}

	public function getVQMod($vqmod_id) {
		$data = $this->getVQMods(['filter_vqmod_id' => $vqmod_id]);

		if (!empty($data) && is_array($data)) {
			return reset($data);
		} else {
			return [];
		}
	}

	public function getVQMods($data = []) {
		$files = glob($this->vqmod_dir . 'xml/*.xml*');

		$vqmods = [];

		if ($files) {
			foreach ($files as $file) {
				$file = realpath($file);

				$vqmod_id = bin2hex($file);

				$xml = file_get_contents($file);

				$xml_error = $this->validateXML($xml);

				$skip = false;

				if (!empty($data['filter_vqmod_id'])) {
					if ($vqmod_id != $data['filter_vqmod_id']) {
						$skip = true;
					}
				}

				if (!empty($data['filter_xml'])) {
					if (stripos($xml, $data['filter_xml']) === false) {
						$skip = true;
					}
				}

				if (isset($data['filter_status']) && strlen($data['filter_status'])) {
					if ($data['filter_status']) {
						if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) != 'xml') {
							$skip = true;
						}
					} else {
						if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'xml') {
							$skip = true;
						}
					}
				}

				if ($skip) { // Skip 1
					continue;
				}

				// get XML info: name, author, etc
				$xml_info = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $xml);
				$xml_info = preg_replace('/<!--(.|\s)*?-->/', '', $xml_info);
				libxml_use_internal_errors(true);
				$xml_obj = simplexml_load_string($xml_info, 'SimpleXMLElement', LIBXML_NOCDATA+LIBXML_PARSEHUGE);

				if (!empty($data['filter_name'])) {
					if (stripos(pathinfo($file, PATHINFO_FILENAME), $data['filter_name']) === false && stripos($xml_obj->id, $data['filter_name']) === false) {
						$skip = true;
					}
				}

				if (!empty($data['filter_author'])) {
					if (stripos($xml_obj->author, $data['filter_author']) === false) {
						$skip = true;
					}
				}

				if ($skip) { // Skip 2
					continue;
				}

				$vqmod = [
					'vqmod_id' => $vqmod_id,
					'path' => $file,
					'dirname' => pathinfo($file, PATHINFO_DIRNAME),
					'basename' => pathinfo($file, PATHINFO_BASENAME),
					'filename' => pathinfo($file, PATHINFO_FILENAME),
					'extension' => strtolower(pathinfo($file, PATHINFO_EXTENSION)),
					'filesize' => filesize($file),
					'date_added' => filectime($file) ? date('Y-m-d H:i:s', filectime($file)) : '',
					'date_modified' => filemtime($file) ? date('Y-m-d H:i:s', filemtime($file)) : '',
					// 'sort_order' => 0,
					'name' => isset($xml_obj->id) ? trim((string)$xml_obj->id) : pathinfo($file, PATHINFO_FILENAME),
					'author' => !empty($xml_obj->author) ? trim((string)$xml_obj->author) : '',
					'version' => !empty($xml_obj->version) ? trim((string)$xml_obj->version) : '',
					'xml' => $xml,
					'xml_obj' => $xml_obj,
					'xml_error' => $xml_error,
					'status' => 1
				];

				if ($vqmod['extension'] != 'xml') {
					$vqmod['status'] = 0;
				}

				$vqmods[$vqmod_id] = $vqmod;
			}
		}

		// Sort
		if (!isset($data['sort'])) {
			$data['sort'] = 'name';
		}

		if (!isset($data['order'])) {
			$data['order'] = 'ASC';
		}

		$sort_order = [];

		foreach ($vqmods as $vqmod) {
			if ($data['sort'] == 'name') {
				$sort_order[] = $vqmod['name'];
			} else if ($data['sort'] == 'filename') {
				$sort_order[] = $vqmod['filename'];
			} else if ($data['sort'] == 'version') {
				$sort_order[] = $vqmod['version'];
			} else if ($data['sort'] == 'author') {
				$sort_order[] = $vqmod['author'];
			} else if ($data['sort'] == 'date_added') {
				$sort_order[] = $vqmod['date_added'];
			} else if ($data['sort'] == 'date_modified') {
				$sort_order[] = $vqmod['date_modified'];
			} else if ($data['sort'] == 'status') {
				$sort_order[] = $vqmod['status'];
			} else {
				$sort_order[] = $vqmod['name'];
			}
		}

		if ($data['order'] == 'DESC') {
			array_multisort($sort_order, SORT_DESC, SORT_NATURAL, $vqmods);
		} else {
			array_multisort($sort_order, SORT_NATURAL, $vqmods);
		}

		// Pagination
		if (isset($data['limit']) && (int)$data['limit'] && count($vqmods) > (int)$data['limit']) {
			$chunks = array_chunk($vqmods, (int)$data['limit'], true);

			if (isset($data['start'])) {
				$data['page'] = floor((int)$data['start'] / (int)$data['limit']) + 1;
			}

			if (isset($data['page'])) {
				$data['page'] = (int)$data['page'] < 1 ? 0 : (int)$data['page'];
				$vqmods = !empty($chunks[$data['page'] - 1]) ? $chunks[$data['page'] - 1] : [];
			}
		}

		return $vqmods;
	}

	public function getModifications($data = []) {
		$vqmods = $this->getVQMods($data);

		$changes = [];

		$mod_paths = [];

		foreach ($vqmods as $vqmod_id => $vqmod) {
			$change = [];

			if (isset($vqmod['xml_obj']->file)) {
				foreach ($vqmod['xml_obj']->file as $nod_file) {
					$attributes = $nod_file instanceof \SimpleXMLElement ? $nod_file->attributes() : new \stdClass();
					$name = trim((string)$attributes->name);
					$path = trim((string)$attributes->path); // optional

					if ($path && !$name) {
						$name = $path;
						$path = '';
					}

					$paths = array_filter(array_unique(array_map('trim', explode(',', $path))));
					$names = array_filter(array_unique(array_map('trim', explode(',', $name))));

					if (!empty($paths)) {
						foreach ($paths as $path) {
							foreach ($names as $name) {
								$p = trim(trim($path, '\\/ ') . '/' . trim($name, '\\/ '), '\\/ ');
								$mod_paths[$p][$vqmod_id] = $vqmod_id;
							}
						}
					} else if (!empty($names)) {
						$path = '';
						foreach ($names as $name) {
							$p = trim(trim($path, '\\/ ') . '/' . trim($name, '\\/ '), '\\/ ');
							$mod_paths[$p][$vqmod_id] = $vqmod_id;
						}
					}
				}
			}
		}

		$sort_order = [];
		foreach ($mod_paths as $path => $mod_path) {
			$sort_order[] = $path;
			$mod_paths[$path] = array_values($mod_path);
		}

		array_multisort($sort_order, SORT_NATURAL, $mod_paths);

		return $mod_paths;
	}

	public function enableVQMod($vqmod_id) {
		$filename_old = hex2bin($vqmod_id);

		if (is_file($filename_old)) {
			$pathinfo = pathinfo($filename_old);

			$filename_new = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.xml';

			rename($filename_old, $filename_new);

			return bin2hex(realpath($filename_new));
		}
	}

	public function disableVQMod($vqmod_id) {
		$filename_old = hex2bin($vqmod_id);

		if (is_file($filename_old)) {
			$pathinfo = pathinfo($filename_old);

			$filename_new = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.xml_';

			rename($filename_old, $filename_new);

			return bin2hex(realpath($filename_new));
		}
	}

	public function deleteVQMod($vqmod_id) {
		$filename_old = hex2bin($vqmod_id);

		$trash_folder = $this->vqmod_dir . 'xml/trash/';

		if (!is_dir($trash_folder)) {
			mkdir($trash_folder, 0777);
		}

		// move file to trash folder
		if (is_file($filename_old)) {
			$pathinfo = pathinfo($filename_old);

			$filename_new = $trash_folder . $pathinfo['basename'];

			rename($filename_old, $filename_new);
		}
	}

	public function clearOCCache() {

	}

	public function clearCache() {
		$files = glob($this->vqmod_dir . '*.cache', GLOB_NOSORT);
		if ($files) {
			foreach ($files as $file) {
				@unlink($file);
			}
		}

		$files = glob($this->vqmod_dir . 'vqcache/*', GLOB_NOSORT);
		if ($files) {
			foreach ($files as $file) {
				@unlink($file);
			}
		}
	}

	public function getLog($vqmod_id = '') {
		$log = '';

		if (is_dir($this->vqmod_dir . 'logs') && is_readable($this->vqmod_dir . 'logs')) {
			// VQMod 2.2.0 and later logs
			$files = glob($this->vqmod_dir . 'logs/*.log', GLOB_NOSORT);
			$files_size = 0;

			if ($files) {
				foreach ($files as $file) {
					$files_size += filesize($file);
				}

				// Error if log files are larger than 10MB combined
				if ($files_size > (10 * 1024 * 1024)) {
					//$json['error_warning'] = sprintf($this->language->get('error_log_size'), round(($files_size / (1024*1024)), 2));
					$log = sprintf($this->language->get('error_log_size'), round(($files_size / (1024*1024)), 2));
				} else {
					foreach ($files as $file) {
						$log .= str_pad(basename($file), 70, '*', STR_PAD_BOTH) . "\n";
						$log .= file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
					}
				}
			}
		}

		if ($vqmod_id) {
			$vqmod_id = hex2bin($vqmod_id);

			$logs = explode($this->vqmod_log_separator, $log);

			$log = [];

			foreach ($logs as $l) {
				$basename = pathinfo($vqmod_id, PATHINFO_BASENAME);
				if (stripos($l, 'xml/' . $basename)) {
					$log[] = $l;
				}
			}

			$log = implode($this->vqmod_log_separator, $log);

			$log = trim($log) ? $log . $this->vqmod_log_separator : $log;
		}

		return $log;
	}

	public function clearLog() {
		$files = glob($this->vqmod_dir . 'logs/*', GLOB_NOSORT);
		if ($files) {
			foreach ($files as $file) {
				@unlink($file);
			}
		}
	}

	public function validateXML($xml) {
		$xml_errors = [];

		$xml = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $xml);
		//$xml = preg_replace('/<!--(.|\s)*?-->/', '', $xml); // remove xml comments
		libxml_clear_errors();
		libxml_use_internal_errors(true);
		$xml_obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA+LIBXML_PARSEHUGE);
		if (libxml_get_errors()) {
			$xml_errors = libxml_get_errors();
			libxml_clear_errors();
		}

		$xml_errors = json_decode(json_encode($xml_errors), true);

		$xml_structure_errors = [];
		$xml_obj = json_decode(json_encode($xml_obj), true);
		if (stripos($xml, '<modification') === false) {
			$xml_structure_errors[] = [
				'level' => 3,
				'code' => 0,
				'column' => 0,
				'message' => '<modification> tag not found',
				'file' => '',
				'line' => 0,
			];
		}

		$checks = ['id', /*'version', 'vqmver', 'author',*/ 'file'];

		foreach ($checks as $field) {
			if (!empty($xml_obj) && !isset($xml_obj[$field])) {
				$xml_structure_errors[] = [
					'level' => 3,
					'code' => 0,
					'column' => 0,
					'message' => '<' . $field . '> tag not found',
					'file' => '',
					'line' => 0,
				];
			}
		}

		$xml_errors = array_merge($xml_structure_errors, $xml_errors);

		return $xml_errors;
	}

	public function errorXML2string($errors, $html = true) {
		if (empty($errors)) return '';

		$html = false;

		$string = '';

		$errors = json_decode(json_encode($errors), true);

		foreach ($errors as $error) {
			$line = '';
			if (!empty($error['line'])) {
				$line .= ($line ? ' ' : '') . 'line:' . $error['line'];
			}

			if (!empty($error['column'])) {
				$line .= ($line ? ' ' : '') . 'col:' . $error['column'];
			}

			if (!empty($error['message']) && trim($error['message'])) {
				$line .= ($line ? ' ' : '') . '' . trim($error['message']);
			}

			$string .= trim($line) . ($html ? '|br|' : '') . PHP_EOL;
		}

		// $string = str_ireplace(['<!--', '-->'], ['&#x3C;!--', '--&#x3E;'], $string);
		$string = htmlentities($string, ENT_QUOTES, 'UTF-8');

		$string = preg_replace('/[ ]{2,}|[\t]/', ' ', trim($string)); // whitespaces
		// $string = trim(preg_replace('/\s+/g', '', $string)); // whitespaces

		if ($html) {
			$string = str_replace('|br|', '<br>', $string);
		}

		return trim($string);
	}

}
