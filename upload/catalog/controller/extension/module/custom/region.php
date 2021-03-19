<?php
class ControllerExtensionModuleCustomRegion extends Controller {

	static $fields = array();

	public function index() {

    $this->load->language('extension/module/custom');
    $this->load->model('extension/module/custom');

    $location = $this->model_extension_module_custom->getCurrentLocation();
    $region_information = $this->model_extension_module_custom->getRegionInformation($location['zone_id']);

    $data['region'] = $region_information['name'];
    $data['region_id'] = $region_information['zone_id'];

    // Regions
		// $this->load->model('extension/module/custom');
    // $data['regions'] = $this->model_extension_module_custom->getRegions();

    $this->response->setOutput($this->load->view('extension/module/custom/region', $data));

  }

  public function update(){

    $json = array();

    $this->load->model('extension/module/custom');
    if (isset($this->request->post['region_id'])) {

      $zone_id = $this->request->post['region_id'];

      // Country
      $country_id = (int)$this->model_extension_module_custom->getCountryIdFromZoneId($zone_id);
      $country_information = $this->model_extension_module_custom->getCountryInformation($country_id);
      if ($country_information){
        $this->session->data['shipping_address'] = array_merge($this->session->data['shipping_address'], $country_information);
      }

      // Zone
      $zone_information = $this->model_extension_module_custom->getZoneInformation($zone_id);
      if ($zone_information) {
        $this->session->data['shipping_address'] = array_merge($this->session->data['shipping_address'], $zone_information);
      }

      $json['status'] = true;

    }

		$this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));

  }

  public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$this->load->model('extension/module/custom');

			$filter_data = array(
				'filter_name' => $this->request->get['filter_name'],
				'start'       => 0,
				'limit'       => 5
			);

			$results = $this->model_extension_module_custom->getRegions($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'zone_id' => $result['zone_id'],
					'name'    => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}

		$sort_order = array();

		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $json);

		$this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
	}

}