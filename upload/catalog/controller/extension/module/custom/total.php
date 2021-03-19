<?php
class ControllerExtensionModuleCustomTotal extends Controller {
	public function index() {

		if (!$this->config->get('module_custom_total')) return false;

		$this->load->language('extension/module/custom');
		$this->load->model('extension/module/custom');

		$data['totals'] = array();
		foreach ($this->model_extension_module_custom->getTotals() as $total) {
			$data['totals'][] = array(
				'title' => $total['title'],
				'text'  => $total['value'] !== 0 ? $this->currency->format($total['value'], $this->session->data['currency']) : $this->language->get('text_free')
			);
		}

		return $this->load->view('extension/module/custom/total', $data);

	}
}