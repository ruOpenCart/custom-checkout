<?php
class ControllerExtensionModuleCustomComment extends Controller {
	public function index() {

    if (!$this->config->get('module_custom_comment')) return false;

		$this->load->language('extension/module/custom');

		if (isset($this->session->data['comment'])) {
			$data['comment'] = $this->session->data['comment'];
		} else {
			$data['comment'] = '';
		}

		return $this->load->view('extension/module/custom/comment', $data);

	}

	public function update(){

		$json = array();

		if (!empty($this->request->post['comment'])) {
			$this->session->data['comment'] = $this->request->post['comment'];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

	}

}