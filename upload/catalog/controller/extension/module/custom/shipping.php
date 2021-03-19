<?php
class ControllerExtensionModuleCustomShipping extends Controller {

	static $fields = array();

	public function index() {

		$setting = $this->config->get('module_custom_shipping');
		$this->fields = $setting['fields'];

		$this->load->language('extension/module/custom');
		$this->load->model('extension/module/custom');

		// Блок не нужно отображать
		if (!$setting['status'] || !$this->cart->hasShipping()) {
			$this->session->data['shipping_method'] = array();
			$this->session->data['shipping_address'] = $this->getFullAddress();
			return false;
		};

		// Получаем методы доставки
		$location = $this->model_extension_module_custom->getCurrentLocation();
		$this->session->data['shipping_methods'] = $this->getMethods($location);

		if (empty($this->session->data['shipping_methods'])) {
			$data['error_warning'] = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact'));
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['shipping_methods'])) {
			$data['shipping_methods'] = $this->session->data['shipping_methods'];
		} else {
			$data['shipping_methods'] = array();
		}

		if (empty($this->session->data['shipping_methods'])) {
			$data['error_warning'] = $this->language->get('error_shipping_methods');
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['shipping_method']['code'])) {
			$data['code'] = $this->session->data['shipping_method']['code'];
		} elseif (!empty($this->session->data['shipping_methods'])) {
			$first_main_code = key($this->session->data['shipping_methods']);
			$first_sub_code = key($this->session->data['shipping_methods'][$first_main_code]['quote']);
			$data['code'] = $this->session->data['shipping_methods'][$first_main_code]['quote'][$first_sub_code]['code'];
			$this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$first_main_code]['quote'][$first_sub_code];
		} else {
			$data['code'] = '';
		}

		$shipping_method = '';
		if (!empty($data['code'])) {
			$shipping_method = explode('.', $data['code'])[0];
		}

		if (isset($this->session->data['shipping_address']['address_id'])) {
			$data['address_id'] = $this->session->data['shipping_address']['address_id'];
		} else {
			$data['address_id'] = $this->customer->getAddressId();
		}

		// Custom Fields
		$this->load->model('account/custom_field');
		$custom_fields = array();
		foreach($this->model_account_custom_field->getCustomFields() as $custom_field){
			$custom_fields[$custom_field['custom_field_id']] = $custom_field;
		}

		// Filed List
		$data['fields'] = array();

		foreach($this->fields as $field){

			$alias = $field['name'];

			// Стандартные поля
			if (stripos($field['name'], 'custom_field') === false) {
				$data['fields'][] = array(
					'alias' 			=> $alias,
					'value'				=> (isset($this->session->data['shipping_address'][$alias])) ? $this->session->data['shipping_address'][$alias] : '',
					'show' 				=> (isset($field['method']) && array_search($shipping_method, $field['method']) !== false) ? true : false,
					'required' 		=> (isset($field['required']) && array_search($shipping_method, $field['required']) !== false) ? true : false,
					'validation'	=> $field['validation']
				);

			// Кастомные поля
			} else {
				$custom_field_id = (int)substr($alias, 12);
				$data['fields'][] = array_merge($custom_fields[$custom_field_id], array(
					'alias' 			=> $alias,
					'value' 			=> isset($this->session->data['shipping_address']['custom_field']['address'][$custom_field_id]) ? $this->session->data['shipping_address']['custom_field']['address'][$custom_field_id] : '',
					'show' 				=> (isset($field['method']) && array_search($shipping_method, $field['method'] !== false)) ? true : false,
					'required' 		=> (isset($field['required']) && array_search($shipping_method, $field['required']) !== false) ? true : false,
					'validation'	=> $field['validation']
				));
			}

		}

		// Addresses
		$this->load->model('account/address');
		$data['addresses'] = $this->model_account_address->getAddresses();

		// Countries
		$this->load->model('localisation/country');
		$data['countries'] = $this->model_localisation_country->getCountries();

		// Region Information
		$zone_information = $this->model_extension_module_custom->getZoneInformation($location['zone_id']);
		$data['region']	=	sprintf($this->language->get('button_region'), $zone_information['zone'], $this->url->link('extension/module/custom/region'));

		return $this->load->view('extension/module/custom/shipping', $data);

	}

	public function update(){
		$json = array();

		$setting = $this->config->get('module_custom_shipping');
		$this->fields = $setting['fields'];

		if (!empty($this->request->get['shipping_method'])) {
			$shipping = explode('.', $this->request->get['shipping_method']);
			$shipping_method = $shipping[0];

			foreach($this->fields as $field){
				if (isset($field['method']) && in_array($shipping_method, $field['method'])){
					$json[] = array(
						'name' => str_replace('_', '-', $field['name']),
						'required' => (isset($field['required']) && in_array($shipping_method, $field['required'])) ? true : false
					);
				}
			}

			$this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
			$this->session->data['shipping_code'] = $shipping[0];
		};

		if (!empty($this->request->get['shipping_type'])) {
			$this->session->data['shipping_type'] = $this->request->get['shipping_type'];
		};

		if (!empty($this->request->get['shippnig_address_id'])) {
			$this->session->data['shippnig_address_id'] = $this->request->get['shippnig_address_id'];
		};

		$json = $this->session->data['shipping_method'];

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function saveInput(){

		$json = array();

		// Setting
		$setting = $this->config->get('module_custom_shipping');

		// Type request
		if ($this->request->server['REQUEST_METHOD'] != 'POST') $json['error'] = true;

		// Data
		if (!$json){
			if (!isset($this->request->post['name']) || !isset($this->request->post['value'])) $json['error'] = true;
			$name = $this->request->post['name'];
			$value = $this->request->post['value'];
		}

		// Field
		if (!$json){

			$field = array();
			foreach($setting['fields'] as $f){
				if ($f['name'] == $name){
					$field = $f;
					$field['value'] = $value;
				}
			}

			if (empty($field)) $json['error'] = true;
		}

		// Validate
		if (!$json){
			if ($this->custom->validate($field)) $json['error'] = true;
		}

		if (!$json){
			if (empty($this->session->data['shipping_address'])) {
				$this->session->data['shipping_address'] = array(
					'zone_id' 		=> $this->config->get('config_zone_id'),
					'country_id' 	=> $this->config->get('config_country_id'),
					$name 				=> $value
				);
			} else {
				$this->session->data['shipping_address'][$name] = $value;
			}
			$json['success'] = true;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

	}

	public function save(){

		// unset($this->session->data['shipping_address']);
		// unset($this->session->data['shipping_method']);

		$this->load->model('account/address');
		$this->load->language('extension/module/custom');

		$json = array();

		$setting = $this->config->get('module_custom_shipping');
		$this->fields = $setting['fields'];

		$shipping_address 		= isset($this->session->data['shipping_address']) 		? $this->session->data['shipping_address'] 		: array();
		$shipping_type 				= isset($this->session->data['shipping_type']) 				? $this->session->data['shipping_type'] 			: 'new';
		$shipping_method 			= isset($this->session->data['shipping_method']) 			? $this->session->data['shipping_method'] 		: '';
		$shipping_code 				= isset($this->session->data['shipping_code']) 				? $this->session->data['shipping_code'] 			: '';
		$shipping_address_id 	= isset($this->session->data['shippnig_address_id']) 	? $this->session->data['shippnig_address_id'] : 0;

		// Method
		if (empty($shipping_method)) {
			$json['error']['warning'] = $this->language->get('error_shipping_select');
		}

		// Address
		if (!$json) {

			// Клиент зарегистрированный и адрес существующий
			if ( $this->customer->isLogged() && $shipping_type == 'existing') {

				if (empty($shipping_address_id)) {
					$json['error']['warning'] = $this->language->get('error_address');
				} elseif (!in_array($shipping_address_id, array_keys($this->model_account_address->getAddresses()))) {
					$json['error']['warning'] = $this->language->get('error_address');
				}

				if (!$json) {
					$this->session->data['shipping_address'] = $this->model_account_address->getAddress($shipping_address_id);
				}

			// Новый адреc
			} else {

				// Проверяем адрес на ошибки
				foreach($this->fields as $field){
					$field['value'] = $shipping_address[$field['name']];
					if (isset($field['method']) && array_search($shipping_code, $field['method'])) {
						if (isset($field['required']) && array_search($shipping_code, $field['required']) && $this->custom->validate($field)) {
							if (stripos($field['name'], 'custom_field') === false) {
								$json['error'][$field['name']] = $this->language->get('error_'.$field['name']);
							} else {
								$json['error'][$field['name']] = $this->language->get('error_custom_field');
							}
						}
					}
				}

			}

			if (!$json){

				$address_new = $this->getFullAddress($shipping_address, $shipping_code);

				// Добавляем новый адрес пользователю
				if ($this->customer->isLogged()){
					$this->model_account_address->addAddress($this->customer->getId(), $address_new);
				}

				// Записываем в сессию
				$this->session->data['shipping_address'] = $address_new;

			}

		}

		$json['shipping_address'] = $this->session->data['shipping_address'];
		$json['shipping_method'] = $this->session->data['shipping_method'];

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

	}

	private function getFullAddress($post = array(), $shipping_code = '', $type = ''){

		$this->load->model('extension/module/custom');

		// Персональные данные изначально
		if ($this->customer->isLogged()) {
			$firstname 	= $this->customer->getFirstName();
			$lastname 	= $this->customer->getLastName();
			$telephone 	= $this->customer->getTelephone();
			$email 			= $this->customer->getEmail();
		} else {
			$firstname 	= isset($this->session->data['guest']['firstname']) ? $this->session->data['guest']['firstname'] 	: '';
			$lastname 	= isset($this->session->data['guest']['lastname']) 	? $this->session->data['guest']['lastname'] 	: '';
			$telephone 	= isset($this->session->data['guest']['telephone']) ? $this->session->data['guest']['telephone'] 	: '';
			$email 			= isset($this->session->data['guest']['email']) 		? $this->session->data['guest']['email'] 			: $this->config->get('config_email');
		}

		// Поля по-умолчанию
		$default = array(
			'firstname' 			=> $firstname,
			'lastname' 				=> $lastname,
			'email' 					=> $email,
			'telephone' 			=> $telephone,
			'company' 				=> '',
			'address_1' 			=> '',
			'address_2' 			=> '',
			'city' 						=> '',
			'postcode' 				=> '',
			'zone' 						=> '',
			'zone_id' 				=> $this->config->get('config_zone_id'),
			'zone_code' 			=> '',
			'country' 				=> '',
			'country_id' 			=> $this->config->get('config_country_id'),
			'iso_code_2'			=> '',
			'iso_code_3'			=> '',
			'default' 				=> true,
			'country_id' 			=> '',
			'address_format' 	=> '',
		);

		// Новый адрес
		$address = array();

		// Восстанавливаем custom-поля
		foreach($this->fields as $field){
			$name = $field['name'];
			if (isset($post[$name]) && isset($field['method']) && array_search($shipping_code, $field['method'])) {
				if (stripos($name, 'custom_field') === false) {
					$address[$name] = $post[$name];
				} else {
					$custom_field_id = (int)str_replace('custom_field', '', $name);
					if ($this->customer->isLogged()){
						$address['custom_field'][$custom_field_id] = $post[$name];
					} else {
						$address['custom_field']['address'][$custom_field_id] = $post[$name];
					}
					unset($address[$key]);
				}
			}
		}

		$new_address = array_merge($default, $address);

		// Информация о стране
		$country_info = $this->model_extension_module_custom->getCountryInformation($new_address['country_id']);

		// Информация о регионе
		$zone_info = $this->model_extension_module_custom->getZoneInformation($new_address['zone_id']);

		return array_merge($new_address, $country_info, $zone_info);
	}

	private function getMethods($location){

		$this->load->model('setting/extension');
		$this->load->model('extension/module/custom');

		$method_data = array();
		foreach ($this->model_setting_extension->getExtensions('shipping') as $result) {
			if ($this->config->get('shipping_'.$result['code'].'_status')) {
				$this->load->model('extension/shipping/' . $result['code']);

				$quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($location);

				if ($quote) {
					$method_data[$result['code']] = array(
						'title'      => $quote['title'],
						'quote'      => $quote['quote'],
						'sort_order' => $quote['sort_order'],
						'error'      => $quote['error']
					);
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

	private function getAddressById($address_id){

		$this->load->model('account/address');

		return $this->model_account_address->getAddress($address_id);

	}

}