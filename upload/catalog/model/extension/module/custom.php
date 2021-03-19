<?php
class ModelExtensionModuleCustom extends Model {

	public function getCountryInformation($country_id){
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$country_id . "'");
    if ($query->num_rows) {
      return array(
        'country_id'      => $query->row['country_id'],
        'country'         => $query->row['name'],
        'iso_code_2'      => $query->row['iso_code_2'],
        'iso_code_3'      => $query->row['iso_code_3'],
        'address_format'  => $query->row['address_format'],
      );
    } else {
      return array();
    }
  }

  public function getZoneInformation($zone_id){
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$zone_id . "'");
    if ($query->num_rows) {
      return array(
				'zone_id'      		=> $query->row['zone_id'],
        'zone'            => $query->row['name'],
        'zone_code'       => $query->row['code']
      );
    } else {
      return array();
    }
	}

	public function getCountryIdFromZoneId($zone_id){
		$query = $this->db->query("SELECT country_id FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$zone_id . "'");
		if ($query->num_rows) {
      return $query->row['country_id'];
    } else {
      return 0;
    }
	}

	// Регион + страна
	public function getRegionInformation($zone_id){
		$query = $this->db->query("
			SELECT
				z.zone_id,
				CONCAT(z.name, ' (', c.name, ')') AS name
			FROM
				`oc_zone` z
			LEFT JOIN `oc_country` c ON
				(z.country_id = c.country_id)
			WHERE
				z.status = '1' AND
				c.status = '1' AND
				z.zone_id = '".(int)$zone_id."'
		");

		return $query->row;
	}

	public function getRegions($data = array()) {

		$sql = "
			SELECT
				z.zone_id,
				c.country_id,
				CONCAT(z.name, ' (', c.name, ')') AS name
			FROM
				`oc_zone` z
			LEFT JOIN `oc_country` c ON
				(z.country_id = c.country_id)
			WHERE
				z.status = '1' AND
				c.status = '1'
		";

		if (!empty($data['filter_name'])){
			$sql .= "AND LCASE(z.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
		}

		$sql .= "ORDER BY c.country_id, z.name";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 10;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		// var_dump($sql);

		$query = $this->db->query($sql);

		return $query->rows;
	}

  public function getTotals(){

		$this->load->model('setting/extension');

		$totals = array();
		$taxes = $this->cart->getTaxes();
		$total = 0;

		// Because __call can not keep var references so we put them into an array.
		$total_data = array(
			'totals' => &$totals,
			'taxes'  => &$taxes,
			'total'  => &$total
		);

		// Display prices
		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$sort_order = array();

			$results = $this->model_setting_extension->getExtensions('total');

			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
			}

			array_multisort($sort_order, SORT_ASC, $results);

			foreach ($results as $result) {
				if ($this->config->get('total_' . $result['code'] . '_status')) {
					$this->load->model('extension/total/' . $result['code']);

					// We have to put the totals in an array so that they pass by reference.
					$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
				}
			}

			$sort_order = array();

			foreach ($totals as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $totals);

		}

		return $totals;

  }

  public function getCurrentLocation(){

		$location = array();

		if (!empty($this->session->data['shipping_address']['country_id'])) {
			$location['country_id'] = $this->session->data['shipping_address']['country_id'];	
		} elseif (!empty($this->session->data['prmn.city_manager'])){
			$location['country_id'] = $this->progroman_city_manager->getCountryId();
		} else {
			$location['country_id'] = $this->config->get('config_country_id');
		}

		if (!empty($this->session->data['shipping_address']['zone_id'])) {
			$location['zone_id'] = $this->session->data['shipping_address']['zone_id'];
		} elseif (!empty($this->session->data['prmn.city_manager'])){
			$location['zone_id'] = $this->progroman_city_manager->getZoneId();
		} else {
			$location['zone_id'] = $this->config->get('config_zone_id');;
		}

		if (!empty($this->session->data['shipping_address']['postcode'])) {
			$location['postcode'] = $this->session->data['shipping_address']['postcode'];
		} else {
			$location['postcode'] = '';
		}

		return $location;
	}


}