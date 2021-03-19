<?php
class ControllerExtensionModuleCustomCart extends Controller {
	public function index() {

		$setting = $this->config->get('module_custom_cart');

    if (!$setting['status']) return false;

		$this->load->language('extension/module/custom');

		$this->load->model('tool/image');
    $this->load->model('tool/upload');

    // Totals
    if (isset($setting['weight']) && $setting['weight']) {
      $data['weight'] = sprintf($this->language->get('text_cart_weight'), $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point')));
    }

    // Weight
    if (isset($setting['totals']) && $setting['totals']) {
      $data['totals'] = sprintf($this->language->get('text_cart_total'), $this->currency->format($this->cart->getSubTotal(), $this->session->data['currency']));
    }

    // Products
    $products = $this->cart->getProducts();
    $products_total = 0;
    $products_quantity = 0;

    // Валидация на все то, что есть в наличии (стандартный ф-л)
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$data['error_warning'] = $this->language->get('error_stock');
		}

    foreach ($products as $product) {
      $product_total = 0;

      foreach ($products as $product_2) {
        if ($product_2['product_id'] == $product['product_id']) {
          $product_total += $product_2['quantity'];
        }
      }

      if ($product['minimum'] > $product_total) {
        $data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
      }

      if ($product['image']) {
        $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
      } else {
        $image = '';
      }

      $option_data = array();

      foreach ($product['option'] as $option) {
        if ($option['type'] != 'file') {
          $value = $option['value'];
        } else {
          $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

          if ($upload_info) {
            $value = $upload_info['name'];
          } else {
            $value = '';
          }
        }

        $option_data[] = array(
          'name'  => $option['name'],
          'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
        );
      }

      // Display prices
      if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
        $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
        $price = $this->currency->format($unit_price, $this->session->data['currency']);
        $total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
        $products_total += $unit_price * $product['quantity'];
      } else {
        $price = false;
        $total = false;
      }

      $recurring = '';

      if ($product['recurring']) {
        $frequencies = array(
          'day'        => $this->language->get('text_day'),
          'week'       => $this->language->get('text_week'),
          'semi_month' => $this->language->get('text_semi_month'),
          'month'      => $this->language->get('text_month'),
          'year'       => $this->language->get('text_year'),
        );

        if ($product['recurring']['trial']) {
          $recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
        }

        if ($product['recurring']['duration']) {
          $recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
        } else {
          $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
        }
      }

      $this->load->model('catalog/product');
      $product_info = $this->model_catalog_product->getProduct($product['product_id']);

      $data['products'][] = array(
        'cart_id'   => $product['cart_id'],
        'thumb'     => $image,
        'name'      => $product['name'],
        'model'     => $product['model'],
        'weight'    => $this->weight->format($product['weight'], $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point')),
        'sku'     	=> $product_info["sku"],
        'option'    => $option_data,
        'recurring' => $recurring,
        'quantity'  => $product['quantity'],
        'stock'     => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
        'reward'    => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
        'price'     => $price,
        'total'     => $total,
        'href'      => $this->url->link('product/product', 'product_id=' . $product['product_id'])
      );

      $products_quantity += $product['quantity'];
    }

    foreach($setting['column'] as $column){
      $data['columns'][] = array(
        'name' => $column,
        'text' => $this->language->get('text_column_'.$column)
      );
    }

    return $this->load->view('extension/module/custom/cart', $data);

	}

	public function update(){
    $this->load->language('extension/module/custom');
    $this->load->model('extension/module/custom');

    $json = array();

    if (!isset($this->request->post['key']) || !isset($this->request->post['event'])) {
      $json['error'] = $this->language->get('warning_request_invalid');
    }

    // Method
		if (!$json){
			if ($this->request->post['event'] == 'update'){
				$this->cart->update($this->request->post['key'], $this->request->post['quantity']);
			} elseif ($this->request->post['event'] == 'remove'){
				$this->cart->remove($this->request->post['key']);
      }
    }

    // Validate
    if (!$json){
      $countProducts = $this->cart->countProducts();
      if ($countProducts == 0){
        $json['empty'] = true;
      }
    }

    // Total
    if (!$json){
      $total = 0;
			foreach($this->model_extension_module_custom->getTotals() as $item){
        $total += $item['value'];
      }
      $json['total'] = sprintf($this->language->get('text_items'), $countProducts + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));
    }

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

  // Use?
	public function clear(){

		$json = array();

		// Clear
		$this->cart->clear();

		$json['empty'] = true;

		unset($this->session->data['shipping_method']);
		unset($this->session->data['shipping_methods']);
		unset($this->session->data['payment_method']);
		unset($this->session->data['payment_methods']);
		unset($this->session->data['reward']);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
  }

  protected function validate(){

		$this->load->language('extension/module/custom');

		$errors = array();

		// Валидация на все то, что есть в наличии (стандартный ф-л)
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$errors['stock'] = $this->language->get('error_stock');
		}

		// Валидация на то, что можно заказать в минимуме (стандартный ф-л)
    $products = $this->cart->getProducts();
    if (empty($products)) $errors['empty'] = true;

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$errors['minimum'][$product['product_id']] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
			}
		}

		return $errors;
	}

}