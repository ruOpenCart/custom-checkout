<?php
class ControllerExtensionModuleCustomModule extends Controller {
	public function index() {

		if (!$this->config->get('module_custom_module')) return false;

		$this->load->language('extension/module/custom');

		$data['modules'] = array();
		$files = glob(DIR_APPLICATION . '/controller/extension/total/*.php');

		if ($files) {
			foreach ($files as $file) {
				$result = $this->load->controller('extension/total/' . basename($file, '.php'));
				if ($result) {
					$data['modules'][] = $result;
				}
			}
		}

		return $this->load->view('extension/module/custom/module', $data);

	}

}