<?php
class ControllerExtensionModuleCustom extends Controller {

	public function index() {

		ini_set('error_reporting', E_ALL);
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);

		// Custom Checkout
		$this->document->addScript('catalog/view/javascript/custom-checkout/dist/custom-checkout.min.js');
		$this->document->addStyle('catalog/view/javascript/custom/custom.css');

		// Magnific Popup
		$this->document->addScript('catalog/view/javascript/jquery/magnific/jquery.magnific-popup.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/magnific/magnific-popup.css');

		$this->load->language('extension/module/custom');
		$this->load->model('extension/module/custom');

		$this->document->setTitle($this->language->get('page_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', '', true)
		);

		if ($this->config->get('module_custom_redirect_cart')) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_cart'),
				'href' => $this->url->link('checkout/cart')
			);
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('extension/module/custom', '', true)
		);

		$status = $this->config->get('module_custom_status');
		$product_count = $this->cart->countProducts();

		if ($status && $product_count > 0) {

			// Вывод всех блоков
			$data['cart'] 		= $this->load->controller('extension/module/custom/cart');
			$data['login'] 		= $this->load->controller('extension/module/custom/login');
			$data['customer'] = $this->load->controller('extension/module/custom/customer');
			$data['shipping'] = $this->load->controller('extension/module/custom/shipping');
			$data['payment'] 	= $this->load->controller('extension/module/custom/payment');
			$data['comment'] 	= $this->load->controller('extension/module/custom/comment');
			$data['module'] 	= $this->load->controller('extension/module/custom/module');
			$data['total'] 		= $this->load->controller('extension/module/custom/total');
			$data['confirm'] 	= $this->config->get('module_custom_confirm');

			// if ($this->config->get('config_checkout_id')) {
			// 	$this->load->model('catalog/information');

			// 	$information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));

			// 	if ($information_info) {
			// 		$data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_checkout_id'), true), $information_info['title'], $information_info['title']);
			// 	} else {
			// 		$data['text_agree'] = '';
			// 	}
			// } else {
			// 	$data['text_agree'] = '';
			// }

			// if (isset($this->session->data['agree'])) {
			// 	$data['agree'] = $this->session->data['agree'];
			// } else {
			// 	$data['agree'] = '';
			// }

		} elseif ($product_count == 0) {
			$data['empty'] = $this->language->get('text_cart_empty');
		} elseif (!$status) {
			$data['warning'] = $this->language->get('error_module_off');
		}

		$data['logged'] = $this->customer->isLogged();

		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');

		$this->response->setOutput($this->load->view('extension/module/custom', $data));
	}

	public function render(){
		if (isset($this->request->get['block'])){
			$block = $this->load->controller('extension/module/custom/'.$this->request->get['block']);
			$this->response->setOutput($block);
		}
	}

}