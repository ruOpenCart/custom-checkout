<?php
class ControllerExtensionModuleCustomLogin extends Controller {
	public function index() {

    if (!$this->config->get('module_custom_login')) {

			if ( $this->config->get('config_checkout_guest') && !$this->config->get('config_customer_price') && !$this->cart->hasDownload() ) {
				$this->session->data['account'] = 'guest';
			} else {
				$this->session->data['account'] = 'register';
			}

      return false;
		}
		
		$data['checkout_guest'] = $this->config->get('config_checkout_guest');

		$this->load->language('extension/module/custom');

		if (isset($this->session->data['account'])) {
			$data['account'] = $this->session->data['account'];
		} else {
			$data['account'] = $this->session->data['account'] = 'register';
		}

		$data['forgotten'] = $this->url->link('account/forgotten', '', true);

		return $this->load->view('extension/module/custom/login', $data);

	}

	public function save(){

		$json = array();

		if (isset($this->request->post['account'])) {
			$this->session->data['account'] = $this->request->post['account'];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

	}

}