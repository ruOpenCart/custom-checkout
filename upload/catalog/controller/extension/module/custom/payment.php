<?php
class ControllerExtensionModuleCustomPayment extends Controller {
	public function index() {

		$this->load->language('extension/module/custom');
		$this->load->model('extension/module/custom');

		$setting = $this->config->get('module_custom_payment');

		// Customer Group
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} elseif (isset($this->session->data['guest']['customer_group_id'])) {
			$customer_group_id = $this->session->data['guest']['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$this->session->data['payment_methods'] = $this->getMethods($customer_group_id);

		if (empty($this->session->data['payment_methods'])) {
			$data['error_warning'] = sprintf($this->language->get('error_no_payment'), $this->url->link('information/contact'));
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['payment_methods'])) {
			$data['payment_methods'] = $this->session->data['payment_methods'];
		} else {
			$data['payment_methods'] = array();
		}

		if (isset($this->session->data['payment_method']['code'])) {
			$data['code'] = $this->session->data['payment_method']['code'];
		} else {
			$data['code'] = '';
		}

		// echo '<pre>';
		// print_r($this->session->data);
		// echo '</pre>';

		return $this->load->view('extension/module/custom/payment', $data);
	}

	public function update(){
		$json = array();

		// Customer Group
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} elseif (isset($this->session->data['guest']['customer_group_id'])) {
			$customer_group_id = $this->session->data['guest']['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		if (!empty($this->request->post['payment_method']) && isset($this->session->data['payment_methods'][$this->request->get['payment_method']])) {
			$this->session->data['payment_method'] = $this->session->data['payment_methods'][$this->request->get['payment_method']];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function getMethods($customer_group_id){

		$this->load->model('setting/extension');
		$this->load->model('extension/module/custom');

		// Allow
		$allow = array();

		// Settings
		$setting = $this->config->get('module_custom_payment');
		foreach($setting['methods'] as $method){
			if (isset($method['customer_group']) && in_array($customer_group_id, $method['customer_group'])){
				$allow[] = $method['name'];
			}
		}

		// Location
		$location = $this->model_extension_module_custom->getCurrentLocation();

		// Totals
		$totals = $this->model_extension_module_custom->getTotals();
		$last_total = array_pop($totals);

		// Recurring
		$recurring = $this->cart->hasRecurringProducts();

		// Payment Methods
		$method_data = array();
		foreach ($this->model_setting_extension->getExtensions('payment') as $result) {

			if ($this->config->get('payment_' . $result['code'] . '_status') && in_array($result['code'], $allow)) {

				$this->load->model('extension/payment/' . $result['code']);
				$method = $this->{'model_extension_payment_' . $result['code']}->getMethod($location, $last_total['value']);

				if ($method)  {
					if ($recurring) {
						if (property_exists($this->{'model_extension_payment_' . $result['code']}, 'recurringPayments') && $this->{'model_extension_payment_' . $result['code']}->recurringPayments()) {
							$method_data[$result['code']] = $method;
						}
					} else {
						$method_data[$result['code']] = $method;
					}
				}
			}
		}

		$sort_order = array();

		foreach ($method_data as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}

		array_multisort($sort_order, SORT_ASC, $method_data);

		return $method_data;
	}

}