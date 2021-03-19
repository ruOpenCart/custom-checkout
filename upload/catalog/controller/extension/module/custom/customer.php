<?php
class ControllerExtensionModuleCustomCustomer extends Controller {
	public function index() {

		$setting = $this->config->get('module_custom_customer');

		$this->load->language('extension/module/custom');

		// Customer groups
		if (isset($this->session->data['guest']['customer_group_id'])){
			$customer_group_id = $this->session->data['guest']['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$data['customer_group_id'] = $customer_group_id;
		$data['customer_groups'] = array();
		if (is_array($this->config->get('config_customer_group_display'))) {
			$this->load->model('account/customer_group');
			foreach ($this->model_account_customer_group->getCustomerGroups()  as $customer_group) {
				if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
					$data['customer_groups'][] = $customer_group;
				}
			}
		}

		// Custom Fields
		$this->load->model('account/custom_field');
		$custom_fields = array();
		foreach($this->model_account_custom_field->getCustomFields() as $custom_field){
			$custom_fields[$custom_field['custom_field_id']] = $custom_field;
		}

		// Filed List
		$data['fields'] = array();

		foreach($setting['fields'] as $field){

			$alias = $field['name'];

			// Стандартные поля
			if (stripos($field['name'], 'custom_field') === false) {
				$data['fields'][] = array(
					'alias' 			=> $alias,
					'value'				=> (isset($this->session->data['guest'][$alias])) ? $this->session->data['guest'][$alias] : '',
					'show' 				=> (isset($field['customer_group']) && in_array($customer_group_id, $field['customer_group'])) ? true : false,
					'required' 		=> (isset($field['required']) && in_array($customer_group_id, $field['required'])) ? true : false,
					'validation'	=> $field['validation'],
					'error'				=> $this->language->get('error_'.$alias)
				);

			// Кастомные поля
			} else {
				$custom_field_id = (int)substr($alias, 12);
				$data['fields'][] = array_merge($custom_fields[$custom_field_id], array(
					'alias' 			=> $alias,
					'value' 			=> isset($this->session->data['guest']['custom_field']['account'][$custom_field_id]) ? $this->session->data['guest']['custom_field']['account'][$custom_field_id] : '',
					'show' 				=> (isset($field['customer_group']) && in_array($customer_group_id, $field['customer_group'])) ? true : false,
					'required' 		=> (isset($field['required']) && in_array($customer_group_id, $field['required'])) ? true : false,
					'validation'	=> $field['validation'],
					'error'				=> $this->language->get('error_custom_field')
				));
			}

		}

		// echo '<pre>';
		// print_r($setting['fields']);
		// echo '</pre>';

		return $this->load->view('extension/module/custom/customer', $data);

	}

	public function update(){
		$json = array();

		$this->load->model('account/custom_field');
		$this->load->model('setting/setting');

		// Customer Group
		if (isset($this->request->get['customer_group_id'])) {
			$customer_group_id = $this->request->get['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$this->session->data['guest']['customer_group_id'] = $customer_group_id;

		$setting = $this->config->get('module_custom_customer');
		foreach($setting['fields'] as $field){

			if ( $this->session->data['account'] == 'register' && ($field['name'] == 'password' || $field['name'] == 'confirm') ){
				$json[] = array(
					'name' => str_replace('_', '-', $field['name']),
					'required' => true
				);
				continue;
			}

			if (isset($field['customer_group']) && in_array($customer_group_id, $field['customer_group'])){
				$json[] = array(
					'name' => str_replace('_', '-', $field['name']),
					'required' => (isset($field['required']) && in_array($customer_group_id, $field['required'])) ? true : false
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function saveInput(){

		$json = array();

		// Setting
		$setting = $this->config->get('module_custom_customer');

		// Type request
		if ($this->request->server['REQUEST_METHOD'] != 'POST') $json['error'] = true;

		// Data
		if (!isset($this->request->post['name']) || !isset($this->request->post['value'])) $json['error'] = true;

		// Field
		if (!$json){

			$field = array();
			foreach($setting['fields'] as $f){
				if ($f['name'] == $this->request->post['name']){
					$field = $f;
					$field['value'] = $this->request->post['value'];
				}
			}

			if (empty($field)) $json['error'] = true;
		}

		// Validate
		if (!$json){
			if ($this->custom->validate($field)) $json['error'] = true;
		}

		if (!$json){
			$this->session->data['guest'][$field['name']] = htmlentities($field['value']);
			$json['success'] = true;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

	}

	public function save(){

		// unset($this->session->data['guest']);

		$json = array();
		$customer = array();

		$this->load->language('extension/module/custom');

		$setting = $this->config->get('module_custom_customer');
		$session = $this->session->data['guest'];

		if (!empty($session['customer_group_id'])) {
			$customer_group_id = $session['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$customer['customer_group_id'] = $customer_group_id;

		// Пробегаемся по полям
		foreach($setting['fields'] as $field){

			$name 			= $field['name'];
			$value 			= isset($session[$name]) ? $session[$name] : '';

			// Поле не приминено для группы пользователей
			if (isset($field['customer_group']) && array_search($customer_group_id, (array)$field['customer_group']) === false) continue;

			// На госте не проверяем пароль и подстверждение
			if ($this->session->data['account'] == 'guest' && ($name == 'password' || $name == 'confirm')) continue;

			// Если поле обязательно, то проверяем его
			if (isset($field['required']) && array_search($customer_group_id, (array)$field['required']) !== false) {

				// Если есть ошибка, то запомниаем её
				if ($this->validate($name, $value, $field['validation'])) {

					if (stripos($name, 'custom_field') === false) {
						$json['error'][$name] = $this->language->get('error_'.$name);
					} else {
						$json['error'][$name] = $this->language->get('error_custom_field');
					}

				} else {
					$customer[$name] = htmlentities(strval($value));
				}

			// Записываем без проверки
			}	elseif (is_array($value)) {
				$customer[$name] = json_encode($value);
			} else {
				$customer[$name] = htmlentities($value);
			}
		}

		// Дополнительная проверка для паролей
		if ( $this->session->data['account'] == 'register'){

			// Если поля пароль нет
			if (!isset($customer['password'])){

				$customer['password'] = '';
				$customer['confirm'] = '';

			// Если есть только поле пароль
			} elseif (isset($customer['password']) && !isset($customer['confirm'])) {

				if (!empty($customer['password'])) {
					$customer['confirm'] = $customer['password'];
				}

			// Если есть оба поля
			} elseif (isset($customer['password']) && isset($customer['confirm'])) {

				if ($customer['password'] !== $customer['confirm']) {
					$json['error']['confirm'] = $this->language->get('error_confirm');
				} elseif (empty($customer['password']) || strlen($customer['password']) < 4) {
					$json['error']['confirm'] = $this->language->get('error_password');
				}

			}
		}

		// Приводим к стандарту и сохраняем в сессию
		$this->session->data['guest'] = $this->full($customer);

		if ($this->customer->isLogged()) {
			$json['warning'] = $this->language->get('warning_logged');
		};

		// При отсутсвии ошибок и необходимости регистрации - регистрируем
		if (!$json && $this->session->data['account'] === 'register' ){
			$json = $this->addCustomer($this->session->data['guest']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

	}

	private function full($customer){

		// Восстанавливаем custom-поля
		foreach($customer as $key => $field){

			if (stripos($key, 'custom_field') !== false) {
				$id = (int)str_replace('custom_field', '', $key);
				if ($this->session->data['account'] === 'register') {
					$customer['custom_field']['account'][$id] = $field;
				} else {
					$customer['custom_field'][$id] = $field;
				}
				unset($customer[$key]);
			}

		}

		// Какие поля должны быть
		$password = bin2hex(random_bytes(10));
		$default = array(
			'customer_group_id' => '',
			'firstname' => '',
			'lastname' => '',
			'email' => '', //$this->config->get('config_email'),
			'telephone' => '',
			'password' => $password,
			'confirm' => $password,
			'fax' => '',
			'custom_field' => array()
		);

		$result = array_merge($default, $customer);

		// Добавляем емейл админа, если он в итоге пустой
		// if (empty($result['email'])) {
		// 	$result['email'] = $this->config->get('config_email');
		// }

		return $result;

	}

	private function addCustomer($customer){

		$json = array();

		$this->load->model('account/customer');
		
		// Проверяем, нет ли с таким email
		if ($this->model_account_customer->getTotalCustomersByEmail($customer['email'])) {
			$this->load->language('account/register');
			$json['warning'] = $this->language->get('error_exists');
		}

		// Регистририуем
		if (!$json) {

			$this->load->model('extension/module/custom/custom');
			$customer_id = $this->model_account_customer->addCustomer($customer);

			// Clear any previous login attempts for unregistered accounts.
			$this->model_account_customer->deleteLoginAttempts($customer['email']);

			// Смиотрим, что там с этой группой можно делать
			$this->load->model('account/customer_group');
			$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer['customer_group_id']);

			if ($customer_group_info && !$customer_group_info['approval']) {
				$this->customer->login($customer['email'], $customer['password']);
			} else {
				$json['redirect'] = $this->url->link('account/success');
			}

			// Add to activity log
			if ($this->config->get('config_customer_activity')) {

				$this->load->model('account/activity');
				$activity_data = array(
					'customer_id' => $customer_id,
					'name'        => $customer['firstname'] . ' ' . $customer['lastname']
				);
				$this->model_account_activity->addActivity('register', $activity_data);

			}

		}

		return $json;
	}

	private function validate($name, $value, $validation = ''){

		// Проверяем на пустоту
		if (empty($value)) {
			return true;

		// Особая проверка для email
		} elseif ($name == 'email' && !preg_match("/.+@.+\..+/i", $value)) {
			return true;

		// Проверка на регулярное выражение
		} elseif (!empty($validation) && !filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $validation )))){
			return true;
		}

		return false;

	}

}