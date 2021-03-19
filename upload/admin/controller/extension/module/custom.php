<?php
class ControllerExtensionModuleCustom extends Controller {
	private $error = array();
	private $version = '2.2.0';

	public function index() {

		// Проверка корзины, полей пользователя, методов оплаты - на дублирование

		$this->load->model('setting/setting');
		$this->load->model('customer/customer_group');
		$this->load->model('extension/module/custom');

		$this->load->language('extension/module/custom');
		$this->document->setTitle($this->language->get('extension_title'));

		$data['extension_title'] = $this->language->get('extension_title');

		// BreadCrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('extension_title'),
			'href' => $this->url->link('extension/module/custom', 'user_token=' . $this->session->data['user_token'], true)
		);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_custom', $this->request->post);
			$data['success'] = $this->language->get('text_success');
		}

		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			$result = $this->model_setting_setting->getSetting('module_custom');
		}

		/* Status */
		if (isset($this->request->post['module_custom_status'])) {
			$data['status'] = $this->request->post['module_custom_status'];
		} elseif (isset($result['module_custom_status'])) {
			$data['status'] = $result['module_custom_status'];
		} else {
			$data['status'] = 0;
		}
		/* End status */

		/* Theme */
		$data['theme_list'] = array(
			'bootstrap3' => $this->language->get('text_bootstrap_3'),
			'bootstrap4' => $this->language->get('text_bootstrap_4')
		);

		if (isset($this->request->post['module_custom_theme'])) {
			$data['theme'] = $this->request->post['module_custom_theme'];
		} elseif (isset($result['module_custom_theme'])) {
			$data['theme'] = $result['module_custom_theme'];
		} else {
			$data['theme'] = 'bootstrap3';
		}
		/* End theme */

		/* Login */
		if (isset($this->request->post['module_custom_login'])) {
			$data['login'] = $this->request->post['module_custom_login'];
		} elseif (isset($result['module_custom_login'])) {
			$data['login'] = $result['module_custom_login'];
		} else {
			$data['login'] = 0;
		}
		/* End login */

		/* Redirect Cart */
		if (isset($this->request->post['module_custom_redirect_cart'])) {
			$data['redirect_cart'] = $this->request->post['module_custom_redirect_cart'];
		} elseif (isset($result['module_custom_redirect_cart'])) {
			$data['redirect_cart'] = $result['module_custom_redirect_cart'];
		} else {
			$data['redirect_cart'] = 1;
		}
		/* End Redirect Cart */

		/* Redirect Checkout */
		if (isset($this->request->post['module_custom_redirect_checkout'])) {
			$data['redirect_checkout'] = $this->request->post['module_custom_redirect_checkout'];
		} elseif (isset($result['module_custom_redirect_checkout'])) {
			$data['redirect_checkout'] = $result['module_custom_redirect_checkout'];
		} else {
			$data['redirect_checkout'] = 1;
		}
		/* End Redirect Checkout */

		/* Comment */
		if (isset($this->request->post['module_custom_comment'])) {
			$data['comment'] = $this->request->post['module_custom_comment'];
		} elseif (isset($result['module_custom_comment'])) {
			$data['comment'] = $result['module_custom_comment'];
		} else {
			$data['comment'] = 0;
		}
		/* End comment */

		/* Module */
		if (isset($this->request->post['module_custom_module'])) {
			$data['module'] = $this->request->post['module_custom_module'];
		} elseif (isset($result['module_custom_module'])) {
			$data['module'] = $result['module_custom_module'];
		} else {
			$data['module'] = 0;
		}
		/* End module */

		/* Total */
		if (isset($this->request->post['module_custom_total'])) {
			$data['total'] = $this->request->post['module_custom_total'];
		} elseif (isset($result['module_custom_total'])) {
			$data['total'] = $result['module_custom_total'];
		} else {
			$data['total'] = 0;
		}
		/* End total */

		/* Confrim */
		if (isset($this->request->post['module_custom_confirm'])) {
			$data['confirm'] = $this->request->post['module_custom_confirm'];
		} elseif (isset($result['module_custom_confirm'])) {
			$data['confirm'] = $result['module_custom_confirm'];
		} else {
			$data['confirm'] = 0;
		}
		/* End confrim */

		/* Cart */
		$data['cart'] = array();
		$data['cart_column'] = $this->model_extension_module_custom->getCartColumns();
		if (isset($this->request->post['module_custom_cart'])) {
			if (isset($this->request->post['module_custom_cart']['status'])) {
				$data['cart']['status'] = $this->request->post['module_custom_cart']['status'];
			}
			if (isset($this->request->post['module_custom_cart']['weight'])) {
				$data['cart']['weight'] = $this->request->post['module_custom_cart']['weight'];
			}
			if (isset($this->request->post['module_custom_cart']['totals'])) {
				$data['cart']['totals'] = $this->request->post['module_custom_cart']['totals'];
			}
			if (isset($this->request->post['module_custom_cart']['column'])) {
				$data['cart']['column'] = $this->request->post['module_custom_cart']['column'];
			}
		} elseif (isset($result['module_custom_cart'])) {
			$data['cart'] = $result['module_custom_cart'];
		} else {
			$data['cart'] = array(
				'status' => 0,
				'weight' => 0,
				'totals' => 0,
				'column' => array()
			);
		}

		if (!isset($data['cart']['status'])) {
			$data['cart']['status'] = 0;
		}

		if (!isset($data['cart']['column'])) {
			$data['cart']['column'] = array();
		}

		if (isset($this->error['cart'])) {
			$data['error_cart'] = $this->error['cart'];
			$data['tab_cart'] = sprintf($this->language->get('error_tab'), $this->language->get('tab_cart'));
		} else {
			$data['error_cart'] = '';
			$data['tab_cart'] = $this->language->get('tab_cart');
		}
		/* End cart */

		/* Customer */
		$data['customer_fields'] = $this->model_extension_module_custom->getCustomerFields();

		if (isset($this->request->post['module_custom_customer'])) {
			$data['customer'] = $this->request->post['module_custom_customer'];
		} elseif (isset($result['module_custom_customer'])) {
			$data['customer'] = $result['module_custom_customer'];
		} else {
			$data['customer'] = array(
				'fields' => array()
			);;
		}

		if (isset($this->error['customer'])) {
			$data['error_customer'] = $this->error['customer'];
			$data['tab_customer'] = sprintf($this->language->get('error_tab'), $this->language->get('tab_customer'));
		} else {
			$data['error_customer'] = '';
			$data['tab_customer'] = $this->language->get('tab_customer');
		}
		/* End customer */

		/* Shipping */
		$data['shipping_fields'] = $this->model_extension_module_custom->getShippingFields();
		$data['shipping_methods'] = $this->model_extension_module_custom->getShippingMethods();

		if (isset($this->request->post['module_custom_shipping'])) {
			$data['shipping'] = $this->request->post['module_custom_shipping'];
		} elseif (isset($result['module_custom_shipping'])) {
			$data['shipping'] = $result['module_custom_shipping'];
		} else {
			$data['shipping'] = array(
				'status' => 0,
				'fields' => array()
			);
		}
		/* End shipping */

		/* Payment */
		$data['payment_methods'] = $this->model_extension_module_custom->getPaymentMethods();

		if (isset($this->request->post['module_custom_payment'])) {
			$data['payment'] = $this->request->post['module_custom_payment'];
		} elseif (isset($result['module_custom_payment'])) {
			$data['payment'] = $result['module_custom_payment'];
		} else {
			$data['payment'] = array(
				'status' => 0,
				'methods' => array()
			);
		}

		if (isset($this->error['payment'])) {
			$data['error_payment'] = $this->error['payment'];
			$data['tab_payment'] = sprintf($this->language->get('error_tab'), $this->language->get('tab_payment'));
		} else {
			$data['error_payment'] = '';
			$data['tab_payment'] = $this->language->get('tab_payment');
		}
		/* End payment */

		// about
		$data['version'] = sprintf($this->language->get('version'), $this->version);
		$data['about_module'] = $this->language->get('about_module');

		// Permission denied
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['action'] = $this->url->link('extension/module/custom', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/custom', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/custom')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ($this->request->post['module_custom_cart']['status'] == 1 && (!isset($this->request->post['module_custom_cart']['column']) || count($this->request->post['module_custom_cart']['column']) == 0)) {
			$this->error['cart'] = $this->language->get('error_cart_nocolumns');
		} elseif (isset($this->request->post['module_custom_cart']['column']) && count($this->request->post['module_custom_cart']['column']) > 0) {
			$column_temp = array();
			foreach($this->request->post['module_custom_cart']['column'] as $column){
				if (!in_array($column, $column_temp)) {
					$column_temp[] = $column;
				} else {
					$this->error['cart'] = $this->language->get('error_cart_double');
				}
			}
		}

		if (!isset($this->request->post['module_custom_customer']['fields']) || count($this->request->post['module_custom_customer']['fields']) == 0) {
			$this->error['customer'] = $this->language->get('error_customer_nofields');
		} elseif (isset($this->request->post['module_custom_customer']['fields']) && count($this->request->post['module_custom_customer']['fields']) > 0) {
			$field_temp = array();
			foreach($this->request->post['module_custom_customer']['fields'] as $field){
				if (!in_array($field['name'], $field_temp)) {
					$field_temp[] = $field['name'];
				} else {
					$this->error['customer'] = $this->language->get('error_customer_double');
				}
			}
		}

		if (!isset($this->request->post['module_custom_payment']['methods']) || count($this->request->post['module_custom_payment']['methods']) == 0) {
			$this->error['payment'] = $this->language->get('error_payment_nomethods');
		} elseif (isset($this->request->post['module_custom_payment']['methods']) && count($this->request->post['module_custom_payment']['methods']) > 0) {
			$method_temp = array();
			foreach($this->request->post['module_custom_payment']['methods'] as $method){
				if (!in_array($method['name'], $method_temp)) {
					$method_temp[] = $method['name'];
				} else {
					$this->error['payment'] = $this->language->get('error_payment_double');
				}
			}
		}

		return !$this->error;
	}

}