<?php
namespace Opencart\Admin\Controller\Extension\ClickerVqmodManager\Module;
class Vqmod extends \Opencart\System\Engine\Controller {
	private $ex_version = '';
	private $vqmod_version = '';
	private $vqmod_installed = 0;
	private $vqmod_dirs = [];
	private $vqmod_dir = '';
	private $vqmod_log_dir = '';
	private $vqmod_logging = 0;
	private $vqmod_url = '';
	private $admin_folder = '';

	private $error = [];

	public function __construct($registry) {
		parent::__construct($registry);

		$this->load->model('extension/clicker_vqmod_manager/module/vqmod');

		$this->vqmod_dirs = $this->model_extension_clicker_vqmod_manager_module_vqmod->getVQModDirs();
		$this->vqmod_dir = $this->model_extension_clicker_vqmod_manager_module_vqmod->getVQModDir();
		$this->vqmod_url = $this->model_extension_clicker_vqmod_manager_module_vqmod->getVQModUrl();
		$this->admin_folder = $this->model_extension_clicker_vqmod_manager_module_vqmod->getAdminFolder();

		$vqmod_vars = class_exists('VQMod') ? get_class_vars('VQMod') : [];
		$this->vqmod_installed = !empty($vqmod_vars['_vqversion']) ? $vqmod_vars['_vqversion'] : 0;
		$this->vqmod_version = !empty($vqmod_vars['_vqversion']) ? $vqmod_vars['_vqversion'] : '';
		$this->vqmod_logging = !empty($vqmod_vars['logging']) ? $vqmod_vars['logging'] : 0;
	}

	public function index() {
		$this->load->language('extension/clicker_vqmod_manager/module/vqmod');

		$this->document->setTitle(strip_tags($this->language->get('heading_title')));

		$data['heading_title'] = strip_tags($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		// TODO add module_vqmod_status = 1 to oc_setting to show it enabled in settings

		$this->getList();
	}

	protected function buildUrl($url = '', $params = ['sort', 'order', 'page', 'filter_name', 'filter_xml', 'filter_author', 'filter_status', 'filter_date_from', 'filter_date_till']) {
		foreach ($params as $param) {
			if (isset($this->request->get[$param])) {
				if (!is_int($this->request->get[$param])) {
					$url .= '&' . $param . '=' . urlencode(html_entity_decode($this->request->get[$param], ENT_QUOTES, 'UTF-8'));
				} else {
					$url .= '&' . $param . '=' . $this->request->get[$param];
				}
			}
		}

		return $url;
	}

	public function add() {
		$this->load->language('extension/clicker_vqmod_manager/module/vqmod');

		$this->document->setTitle($this->language->get('text_edit'));

		if (isset($this->request->post['xml'])) {
			$this->request->post['xml'] = html_entity_decode($this->request->post['xml'], ENT_QUOTES, 'UTF-8');
		}

		if (isset($this->request->post['filename']) && stripos($this->request->post['filename'], '.xml')) {
			$this->request->post['filename'] = str_ireplace(['.xml', '.xml_'], '', $this->request->post['filename']);
		}

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateForm()) {

			$vqmod_id = $this->model_extension_clicker_vqmod_manager_module_vqmod->addVQMod($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildUrl();

			$this->response->redirect($this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('extension/clicker_vqmod_manager/module/vqmod');

		$this->document->setTitle($this->language->get('text_edit'));

		if (isset($this->request->post['xml'])) {
			$this->request->post['xml'] = html_entity_decode($this->request->post['xml'], ENT_QUOTES, 'UTF-8');
		}

		if (isset($this->request->post['filename']) && stripos($this->request->post['filename'], '.xml')) {
			$this->request->post['filename'] = str_ireplace(['.xml', '.xml_'], '', $this->request->post['filename']);
		}

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateForm()) {
			$vqmod_id = $this->model_extension_clicker_vqmod_manager_module_vqmod->editVQMod($this->request->get['vqmod_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			if (!empty($this->request->get['refresh'])) {
				$this->model_extension_clicker_vqmod_manager_module_vqmod->clearCache();
			}

			$url = $this->buildUrl();

			$this->response->redirect($this->url->link('extension/clicker_vqmod_manager/module/vqmod.edit', 'vqmod_id=' . $vqmod_id . '&user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function copy() {
		$this->load->language('extension/clicker_vqmod_manager/module/vqmod');

		$this->document->setTitle($this->language->get('text_edit'));

		if (isset($this->request->post['selected']) && $this->validateEdit()) {
			foreach ($this->request->post['selected'] as $vqmod_id) {
				$this->model_extension_clicker_vqmod_manager_module_vqmod->copyVQMod($vqmod_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildUrl();

			$this->response->redirect($this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function delete() {
		$this->load->language('extension/clicker_vqmod_manager/module/vqmod');

		$this->document->setTitle($this->language->get('text_edit'));

		if (isset($this->request->post['selected'])/* && $this->validateDelete()*/) {
			foreach ($this->request->post['selected'] as $vqmod_id) {
				$this->model_extension_clicker_vqmod_manager_module_vqmod->deleteVQMod($vqmod_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildUrl();

			$this->response->redirect($this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function enable() {
		$this->load->language('extension/clicker_vqmod_manager/module/vqmod');

		$this->document->setTitle($this->language->get('text_edit'));

		if (isset($this->request->get['vqmod_id'])) {
			$vqmod_id = $this->model_extension_clicker_vqmod_manager_module_vqmod->enableVQMod($this->request->get['vqmod_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildUrl();

			$this->response->redirect($this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function disable() {
		$this->load->language('extension/clicker_vqmod_manager/module/vqmod');

		$this->document->setTitle($this->language->get('text_edit'));

		if (isset($this->request->get['vqmod_id'])) {
			$vqmod_id = $this->model_extension_clicker_vqmod_manager_module_vqmod->disableVQMod($this->request->get['vqmod_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildUrl();

			$this->response->redirect($this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function clear_cache() {
		$this->load->language('extension/clicker_vqmod_manager/module/vqmod');

		$this->document->setTitle($this->language->get('text_edit'));

		$this->model_extension_clicker_vqmod_manager_module_vqmod->clearCache();

		$this->session->data['success'] = $this->language->get('text_success');

		$url = $this->buildUrl();

		$this->response->redirect($this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . $url, true));

		$this->getList();
	}

	public function get_log() {
		$json = [];

		$vqmod_id = isset($this->request->get['vqmod_id']) ? $this->request->get['vqmod_id'] : '';

		$json['log'] =  $this->model_extension_clicker_vqmod_manager_module_vqmod->getLog($vqmod_id);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function clear_log() {
		$json = [];

		$vqmod_id = isset($this->request->get['vqmod_id']) ? $this->request->get['vqmod_id'] : '';

		$this->model_extension_clicker_vqmod_manager_module_vqmod->clearLog($vqmod_id);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function get_modifications() {
		$this->load->language('extension/clicker_vqmod_manager/module/vqmod');

		$json = [];

		$vqmod_id = isset($this->request->get['vqmod_id']) ? $this->request->get['vqmod_id'] : '';

		$modifications = $this->model_extension_clicker_vqmod_manager_module_vqmod->getModifications(['filter_vqmod_id' => $vqmod_id]);
		$vqmods = $this->model_extension_clicker_vqmod_manager_module_vqmod->getVQMods(['filter_vqmod_id' => $vqmod_id]);

		$data['modifications'] = [];

		foreach ($modifications as $path => $mods) {
			$modification = [
				'path' => $path,
				'vqmods' => []
			];

			foreach ($mods as $vqmod_id) {
				if (isset($vqmods[$vqmod_id])) {
					$vqmods[$vqmod_id]['edit'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.edit', 'user_token=' . $this->session->data['user_token'] . '&vqmod_id=' . $vqmod_id, true);

					$modification['vqmods'][$vqmod_id] = $vqmods[$vqmod_id];
				}
			}

			$data['modifications'][$path] = $modification;
		}

		$data['user_token'] = $this->session->data['user_token'];

		$json['html'] = $this->load->view('extension/clicker_vqmod_manager/module/vqmod_modification', $data);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function vendor() {
		$this->load->language('extension/clicker_vqmod_manager/module/vqmod');

		$json = [];

		// find any version of uploaded vqmod.zip
		$files = glob(DIR_EXTENSION . 'clicker_vqmod_manager/system/library/installer/vqmod*opencart.zip');

		$file = $files ? end($files) : '';

		// $file = DIR_STORAGE . 'vendor/clicker_vqmod_manager/vqmod-opencart.zip';

		if (!empty($file) && is_file($file)) {
			$data['vqmod_vendor'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.vendor', 'user_token=' . $this->session->data['user_token'], true);

			$zip = new \ZipArchive();

			if ($zip->open($file)) {

				$extract = [];

				for ($i = 0; $i < $zip->numFiles; $i++) {
					$source = $zip->getNameIndex($i);

					$destination = ltrim($source, '\\/');
					if (stripos($destination, 'vqmod') !== 0) continue;

					$path = $destination;

					$base = DIR_OPENCART;

					$extract[] = [
						'source'      => $source,
						'destination' => $destination,
						'base'        => $base,
						'path'        => $path
					];
				}

				$zip->close();

				if ($extract) {
					foreach ($extract as $copy) { // create folders structure
						if (substr($copy['source'], -1) === '/' && !is_dir(DIR_OPENCART . $copy['source'])) {
							if (!mkdir(DIR_OPENCART . $copy['source'], 0777)) {
								$json['error'] = 'Error creating ' . DIR_OPENCART . $copy['source'] . ' folder';
							}
						}
					}

					if (empty($json['error'])) {
						foreach ($extract as $copy) {
							// If check if the path is not directory and check there is no existing file
							if (substr($copy['path'], -1) != '/' && is_file($copy['base'] . $copy['path'])) {
								@unlink($copy['base'] . $copy['path']);
							}
							if (substr($copy['path'], -1) != '/' && copy('zip://' . $file . '#' . $copy['source'], $copy['base'] . $copy['path'])) {}
						}
					}
				}

				if (empty($json['error']) && is_dir(DIR_EXTENSION . 'clicker_vqmod_manager/system/library/installer/xml') && is_dir(DIR_OPENCART . 'vqmod/xml/')) {
					$files = glob(DIR_EXTENSION . 'clicker_vqmod_manager/system/library/installer/xml/*', GLOB_NOSORT);

					if ($files) {
						foreach ($files as $file) {
							$basename = basename($file);
							copy($file, DIR_OPENCART . 'vqmod/xml/' . $basename);
						}
					}
				}
			} else {
				$json['error'] = $this->language->get('error_zip');
			}

			$json['success'] = $this->language->get('text_extract_success');
		} else {
			$json['error'] = 'vqmod-opencart.zip not found!';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function upload() {
		$this->load->language('extension/clicker_vqmod_manager/module/vqmod');

		$json = [];

		// Check for any install directories
		if (isset($this->request->files['file']['name'])) {
			$filename = basename($this->request->files['file']['name']);

			$pathinfo = pathinfo($this->request->files['file']['name']);

			if ((oc_strlen($filename) < 1) || (oc_strlen($filename) > 128)) {
				$json['error'] = $this->language->get('error_filename');
			}

			if (!in_array($pathinfo['extension'], ['xml', 'xml_'])) {
				$json['error'] = $this->language->get('error_filetype');
			}

			if (is_file($this->vqmod_dir . 'xml/' . $pathinfo['filename'] . '.xml_') || is_file($this->vqmod_dir . 'xml/' . $pathinfo['filename'] . '.xml')) {
				$json['error'] = $this->language->get('error_exists');
			}

			if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
				$json['error'] = $this->language->get('error_upload_' . $this->request->files['file']['error']);
			}
		} else {
			$json['error'] = $this->language->get('error_upload');
		}

		// $json['error'] = 'test';

		if (empty($json['error'])) {
			$file = $this->vqmod_dir . 'xml/' . $pathinfo['filename'] . '.xml_';

			move_uploaded_file($this->request->files['file']['tmp_name'], $file);

			$json['success'] = $this->language->get('text_upload');
		} else {
			unset($this->request->files['file']['tmp_name']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function download_log() {
		$files = glob($this->vqmod_dir . 'logs/*', GLOB_NOSORT);

		$filename = 'logs';

		// $temp = @tempnam('tmp', 'zip');
		$temp = @tempnam(DIR_CACHE, 'zip');

		$zip = new \ZipArchive();
		$zip->open($temp, \ZipArchive::OVERWRITE);

		if ($files) {
			foreach ($files as $file) {
				if (is_file($file)) {
					$zip->addFile($file, basename($file));
				}
			}
		}

		$zip->close();

		header('Pragma: public');
		header('Expires: 0');
		header('Content-Description: File Transfer');
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename=' . $filename . '_' . date('Y-m-d_H-i') . '.zip');
		header('Content-Transfer-Encoding: binary');
		readfile($temp);
		unlink($temp);
	}

	public function download_vqcache() {
		$files = glob($this->vqmod_dir . 'vqcache/*', GLOB_NOSORT);

		$filename = 'vqcache';

		// $temp = @tempnam('tmp', 'zip');
		$temp = @tempnam(DIR_CACHE, 'zip');

		$zip = new \ZipArchive();
		$zip->open($temp, \ZipArchive::OVERWRITE);

		if ($files) {
			foreach ($files as $file) {
				if (is_file($file)) {
					$zip->addFile($file, basename($file));
				}
			}
		}

		$zip->close();

		header('Pragma: public');
		header('Expires: 0');
		header('Content-Description: File Transfer');
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename=' . $filename . '_' . date('Y-m-d_H-i') . '.zip');
		header('Content-Transfer-Encoding: binary');
		readfile($temp);
		unlink($temp);
	}

	public function download_xml() {
		if (isset($this->request->get['vqmod_id'])) {
			$file = hex2bin($this->request->get['vqmod_id']);

			$filename = basename($file);

			header('Pragma: public');
			header('Expires: 0');
			header('Content-Description: File Transfer');
			header('Content-Type: application/xml');
			header('Content-Disposition: attachment; filename=' . $filename );
			header('Content-Transfer-Encoding: binary');
			readfile($file);

		} else {
			$filename = 'vqxml';

			$temp = @tempnam(DIR_CACHE, 'zip');

			$zip = new \ZipArchive();
			$zip->open($temp, \ZipArchive::OVERWRITE);

			$dir_opencart = str_replace('\\', '/', DIR_OPENCART);

			foreach ($this->vqmod_dirs as $vqmod_dir) {
				$files = glob($vqmod_dir . '*', GLOB_NOSORT);

				$this->log->write($files);

				if ($files) {
					foreach ($files as $file) {
						$dirname = str_replace('\\', '/', pathinfo($file, PATHINFO_DIRNAME));
						$dirname = str_replace($dir_opencart, '', $dirname) . '/';

						if (is_file($file)) {
							$zip->addFile($file, $dirname . basename($file));
						}
					}
				}
			}

			$zip->close();

			header('Pragma: public');
			header('Expires: 0');
			header('Content-Description: File Transfer');
			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename=' . $filename . '_' . date('Y-m-d_H-i') . '.zip');
			header('Content-Transfer-Encoding: binary');
			readfile($temp);
			unlink($temp);
		}
	}

	protected function getList() {
		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_topic'])) {
			$filter_topic = $this->request->get['filter_topic'];
		} else {
			$filter_topic = '';
		}

		if (isset($this->request->get['filter_author'])) {
			$filter_author = $this->request->get['filter_author'];
		} else {
			$filter_author = '';
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} else {
			$filter_status = '';
		}

		if (isset($this->request->get['filter_date_from'])) {
			$filter_date_from = $this->request->get['filter_date_from'];
		} else {
			$filter_date_from = '0000-00-00';
		}

		if (isset($this->request->get['filter_date_till'])) {
			$filter_date_till = $this->request->get['filter_date_till'];
		} else {
			$filter_date_till = '0000-00-00';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'filename';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = $this->buildUrl();

		$data['heading_title'] = strip_tags($this->language->get('text_vqmod_manager'));
		$data['ex_version'] = $this->getVersion();

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		$data['breadcrumbs'][] = [
			'text' => strip_tags($this->language->get('text_vqmod_manager')),
			'href' => $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . $url, true)
		];

		$data['add'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['copy'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.copy', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['enable'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.enable', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['disable'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.disable', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['clear_cache'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.clear_cache', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['get_log'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.get_log', 'user_token=' . $this->session->data['user_token'], true);
		$data['clear_log'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.clear_log', 'user_token=' . $this->session->data['user_token'], true);
		$data['get_modifications'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.get_modifications', 'user_token=' . $this->session->data['user_token'], true);
		$data['upload'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.upload', 'user_token=' . $this->session->data['user_token'], true);
		$data['download_xml'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.download_xml', 'user_token=' . $this->session->data['user_token'], true);
		$data['download_vqcache'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.download_vqcache', 'user_token=' . $this->session->data['user_token'], true);
		$data['download_log'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.download_log', 'user_token=' . $this->session->data['user_token'], true);

		$data['user_token'] = $this->session->data['user_token'];

		$url_filter = $this->buildUrl('', ['sort', 'order', 'page']);

		$data['filter'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . $url_filter, true);
		$data['filter_name'] = !empty($this->request->get['filter_name']) ? $this->request->get['filter_name'] : '';
		$data['filter_author'] = !empty($this->request->get['filter_author']) ? $this->request->get['filter_author'] : '';
		$data['filter_xml'] = !empty($this->request->get['filter_xml']) ? $this->request->get['filter_xml'] : '';
		$data['filter_status'] = (isset($this->request->get['filter_status']) && strlen($this->request->get['filter_status'])) ? $this->request->get['filter_status'] : '';

		$filter_data = [
			'filter_name' => $data['filter_name'],
			'filter_author' => $data['filter_author'],
			'filter_xml' => $data['filter_xml'],
			'filter_status' => $data['filter_status'],
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_pagination_admin'),
			'limit' => $this->config->get('config_pagination_admin')
		];

		$results = $this->model_extension_clicker_vqmod_manager_module_vqmod->getVQMods($filter_data);

		$filter_data['start'] = 0;
		$filter_data['limit'] = 999999999;
		$vqmod_total = count($this->model_extension_clicker_vqmod_manager_module_vqmod->getVQMods($filter_data));

		$data['vqmods'] = [];

		$dir_opencart = str_replace('\\', '/', DIR_OPENCART);

		foreach ($results as $result) {
			$dirname =  str_replace('\\', '/', $result['dirname']);
			$dirname =  str_replace($dir_opencart, '', $dirname) . '/';

			$data['vqmods'][] = [
				'vqmod_id'    	=> $result['vqmod_id'],
				'name'          => $result['name'],
				'dirname'       => $dirname,
				'basename'      => $result['basename'],
				'filesize'		=> number_format(($result['filesize'] / 1024), 2, '.', ' ') . 'KB',
				// 'sort_order'    => $result['sort_order'],
				'date_added'    => strpos($result['date_added'], '0000-00-00') === false ? date('Y-m-d H:i:s', strtotime($result['date_added'])) : '',
				'date_modified' => strpos($result['date_modified'], '0000-00-00') === false ? date('Y-m-d H:i:s', strtotime($result['date_modified'])) : '',
				'status'        => (int)$result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'enabled'       => (int)$result['status'],
				'edit'          => $this->url->link('extension/clicker_vqmod_manager/module/vqmod.edit', 'user_token=' . $this->session->data['user_token'] . '&vqmod_id=' . $result['vqmod_id'] . $url, true),
				'delete'        => $this->url->link('extension/clicker_vqmod_manager/module/vqmod.delete', 'user_token=' . $this->session->data['user_token'] . '&vqmod_id=' . $result['vqmod_id'] . $url, true),
				'enable'        => $this->url->link('extension/clicker_vqmod_manager/module/vqmod.enable', 'user_token=' . $this->session->data['user_token'] . '&vqmod_id=' . $result['vqmod_id'] . $url, true),
				'disable'       => $this->url->link('extension/clicker_vqmod_manager/module/vqmod.disable', 'user_token=' . $this->session->data['user_token'] . '&vqmod_id=' . $result['vqmod_id'] . $url, true),
				'download'      => $this->url->link('extension/clicker_vqmod_manager/module/vqmod.download_xml', 'user_token=' . $this->session->data['user_token'] . '&vqmod_id=' . $result['vqmod_id'], true),
				'author'        => $result['author'],
				'version'       => $result['version'],
				'link'          => !empty($result['xml_obj']->link) ? trim((string)$result['xml_obj']->link) : '',
				'info'          => !empty($result['xml_obj']->info) ? trim((string)$result['xml_obj']->info) : '',
				'xml_error'     => $this->model_extension_clicker_vqmod_manager_module_vqmod->errorXML2string($result['xml_error'], true),
			];
		}

		$data['vqmod_version'] = $this->vqmod_version;
		//$data['vqmod_installer'] = $this->vqmod_url . 'install/index.php';
		$data['vqmod_installer'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.vqmod_installer', 'user_token=' . $this->session->data['user_token'], true);
		$data['vqmod_logging'] = $this->vqmod_logging;
		$query = $this->db->query("SELECT VERSION() AS `version`");
		$data['mysql_version'] = !empty($query->row['version']) ? $query->row['version'] : '';
		$data['php_version'] = phpversion();
		$data['vqmod_path'] = is_dir($this->vqmod_dir) ? $this->vqmod_dir : '';
		$data['vqmod_vendor'] = '';
		if ($files = glob(DIR_EXTENSION . 'clicker_vqmod_manager/system/library/installer/vqmod*opencart.zip')) {
			$data['vqmod_vendor'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.vendor', 'user_token=' . $this->session->data['user_token'], true);
			$data['vqmod_vendor_file'] = basename(end($files));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = [];
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$url = $this->buildUrl($url, ['filter_name', 'filter_xml', 'filter_author', 'filter_status']);

		$data['sort_name'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
		$data['sort_filename'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . '&sort=filename' . $url, true);
		$data['sort_author'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . '&sort=author' . $url, true);
		$data['sort_version'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . '&sort=version' . $url, true);
		$data['sort_status'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);
		$data['sort_date_added'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . '&sort=date_added' . $url, true);
		$data['sort_date_modified'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . '&sort=date_modified' . $url, true);

		$url = $this->buildUrl('', ['sort', 'order', 'filter_name', 'filter_xml', 'filter_author', 'filter_status']);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $vqmod_total,
			'page'  => $page,
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($vqmod_total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination_admin')) > ($vqmod_total - $this->config->get('config_pagination_admin'))) ? $vqmod_total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')), $vqmod_total, ceil($vqmod_total / $this->config->get('config_pagination_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/clicker_vqmod_manager/module/vqmod_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['vqmod_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['filename'])) {
			$data['error_filename'] = $this->error['filename'];
		} else {
			$data['error_filename'] = '';
		}

		if (isset($this->error['xml'])) {
			$data['error_xml_info'] = is_array($this->error['xml']) ? $this->model_extension_clicker_vqmod_manager_module_vqmod->errorXML2string($this->error['xml'], true) : $this->error['xml'];
		} else {
			$data['error_xml_info'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$url = $this->buildUrl();

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		$data['breadcrumbs'][] = [
			'text' => strip_tags($this->language->get('text_vqmod_manager')),
			'href' => $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . $url, true)
		];

		if (!isset($this->request->get['vqmod_id'])) {
			$data['action'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.edit', 'vqmod_id=' . $this->request->get['vqmod_id'] . '&user_token=' . $this->session->data['user_token'] . $url, true);
			$data['edit_refresh'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.edit', 'refresh=1&user_token=' . $this->session->data['user_token'] . $url, true);
		}

		$data['get_log'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.get_log', 'user_token=' . $this->session->data['user_token'], true);
		$data['clear_log'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.clear_log', 'user_token=' . $this->session->data['user_token'], true);
		$data['get_modifications'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod.get_modifications', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('extension/clicker_vqmod_manager/module/vqmod', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['vqmod_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$vqmod_info = $this->model_extension_clicker_vqmod_manager_module_vqmod->getVQMod($this->request->get['vqmod_id']);
		}

		if (!empty($vqmod_info)) {
			$vqmod_info['name'] = isset($vqmod_info['xml_obj']->id) ? trim($vqmod_info['xml_obj']->id) : $vqmod_info['filename'];
			$data['text_form'] .= ' - ' . $vqmod_info['name'];
			$this->document->setTitle($data['text_form']);

			if (empty($data['error_xml_info']) && !empty($vqmod_info['xml_error'])) {
				$data['error_xml_info'] = $this->model_extension_clicker_vqmod_manager_module_vqmod->errorXML2string($vqmod_info['xml_error'], true);
			}
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['vqmod_id'] = isset($this->request->get['vqmod_id']) ? $this->request->get['vqmod_id'] : '';

		$data['filepath'] = $data['vqmod_id'] ? hex2bin($data['vqmod_id']) : '';

		if (isset($this->request->post['xml'])) {
			$data['xml'] = htmlentities($this->request->post['xml'], ENT_QUOTES, 'UTF-8');
		} elseif (isset($this->request->get['vqmod_id'])) {
			$data['xml'] = !empty($vqmod_info['xml']) ? htmlentities($vqmod_info['xml'], ENT_QUOTES, 'UTF-8') : '';
		} else {
			$data['xml'] = htmlentities($this->exampleVQMod(), ENT_QUOTES, 'UTF-8');
		}

		if (isset($this->request->post['filename'])) {
			$data['filename'] = $this->request->post['filename'];
		} elseif (!empty($vqmod_info)) {
			$data['filename'] = $vqmod_info['filename'];
		} else {
			$data['filename'] = '';
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($vqmod_info)) {
			$data['status'] = $vqmod_info['status'];
		} else {
			$data['status'] = 0;
		}

		$data['heading_title'] = strip_tags($this->language->get('text_vqmod_manager'));

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/clicker_vqmod_manager/module/vqmod_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/clicker_vqmod_manager/module/vqmod')) {
			$this->error['warning'] = $this->language->get('error_permission');
			return false;
		}

		$xml_errors = $this->model_extension_clicker_vqmod_manager_module_vqmod->validateXML($this->request->post['xml']);

		if ($xml_errors) {
			$this->error['warning'] = $this->language->get('error_xml');
			$this->error['xml'] = $xml_errors;
			return false;
		}

		if (!(!empty($this->request->post['filename']) && trim($this->request->post['filename']))) {
			$this->error['filename'] = $this->language->get('error_filename');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/clicker_vqmod_manager/module/vqmod')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function getVersion() {
		if (!empty($this->ex_version)) {
			return $this->ex_version;
		}

		$sql = "SELECT `code`, `version` FROM `" . DB_PREFIX . "extension_install` WHERE `code` = 'clicker_vqmod_manager' ORDER BY `date_added` DESC LIMIT 1";

		$query = $this->db->query($sql);

		$this->ex_version = !empty($query->row['version']) ? $query->row['version'] : '';

		return $this->ex_version;
	}

	public function vqmod_installer() {
		$file = $this->vqmod_dir . "install/index.php";

		if (is_file($file)) {
			if (empty($_POST['admin_name']) && !empty($this->admin_folder)) {
				$_POST['admin_name'] = $this->admin_folder;
			}

			require_once($this->vqmod_dir . "install/index.php");

			die();
		} else {
			die('Installer not found: ' . $file);
		}
	}

	protected $events_admin = [
		'clicker_vqmod_manager_ac_common_column_left_a' => [
			'description' => 'VQMod Manager in left column',
			'trigger' => 'admin/controller/common/footer/after',
			'action' => 'extension/clicker_vqmod_manager/module/vqmod.event_column_left_after'
		],
	];
	protected $events_catalog = [];
	protected $startups = [];

	public function install() {
		/*$this->load->model('setting/event');

		foreach ($this->events_admin as $code => $event) {
			if (!$this->model_setting_event->getEventByCode($code)) {
				$this->model_setting_event->addEvent(
					$code,
					$event['description'],
					$event['trigger'],
					$event['action'],
				);
			}
		}

		foreach ($this->events_catalog as $code => $event) {
			if (!$this->model_setting_event->getEventByCode($code)) {
				$this->model_setting_event->addEvent(
					$code,
					$event['description'],
					$event['trigger'],
					$event['action'],
				);
			}
		}

		$this->load->model('setting/startup');

		foreach ($this->startups as $code => $startup) {
			if (!$this->model_setting_startup->getStartupByCode($code)) {
				$this->model_setting_startup->addStartup(
					$code,
					$startup['action'],
				);
			}
		}*/
	}

	public function uninstall() {
		// delete the event triggers
		/*$this->load->model('setting/event');

		foreach ($this->events_admin as $code => $event) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		foreach ($this->events_catalog as $code => $event) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		$this->load->model('setting/startup');

		foreach ($this->startups as $code => $startup) {
			$this->model_setting_startup->deleteStartupByCode($code);
		}*/
	}

	// rewrite template route
	public function event_column_left_after(&$route, &$args, &$output) {
	}

	protected function exampleVQMod() {
		$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<modification>
	<id>Example VQMod</id>
	<version>1.0.0</version>
	<vqmver required="true">2.6.6</vqmver>
	<author>Extension Author</author>
	<link>https://opencart.com/extension-page.html</link>
	<info>Short description</info>

	<file name="mypath/myfile.php" error="skip">
		<operation error="skip" info="Info">
			<search position="replace" trim="true"><![CDATA[
				search string
			]]></search>
			<add trim="true"><![CDATA[
				replace string
			]]></add>
		</operation>
	</file>
</modification>
XML;
		return $xml;
	}
}
