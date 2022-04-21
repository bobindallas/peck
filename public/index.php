<?php

// pull in guzzle etc via composer
require '../vendor/autoload.php';

use GuzzleHttp\Client;

$food_truck = new FoodTruck();
$f_dat      = $food_truck->process_request();

print_r($f_dat);

/*
 *
 * Class FoodTruck
 *
 */
class FoodTruck {

	// misc class vars
	protected $ft_url      = 'https://data.sfgov.org/resource/rqzj-sfat.json';
	protected $ps_url      = 'http://api.positionstack.com/v1/forward?access_key=9614e7ce372b96e6fba7db60141be1b7&output=json&limit=1';
	protected $my_location = '';
	protected $my_lat      = 0;
	protected $my_lon      = 0;
	protected $distance    = 0;
	protected $type        = '';
	protected $items       = '';
	protected $result      = [];

	/*
	 * constructor
	 */
	public function __construct() {

		$ft_client = new \GuzzleHttp\Client();

		$this->my_location = (isset($_REQUEST['my_location'])) ? urlencode($_REQUEST['my_location']) : null;
		$this->distance    = (isset($_REQUEST['distance']) && is_numeric($_REQUEST['distance'])) ? abs($_REQUEST['distance']) : 0;
		$this->type        = (isset($_REQUEST['type'])) ? urldecode($_REQUEST['type']) : null;
		$this->items       = (isset($_REQUEST['fooditems'])) ? urldecode($_REQUEST['fooditems']) : null;

		// would probably cache this daily in a prod env
		$this->ft_response = $ft_client->request('GET', $this->ft_url);
		$this->ft_data     = json_decode($this->ft_response->getBody(),1);

	}

	/*
	 * main handler
	 */
	public function process_request() {

		$this->get_my_location(); // did they send a location (and a search distance)
		$this->filter_distance(); // if filtering by location / distance - do it here
		$this->filter_results();  // filter by other misc options

		return $this->result;
	
	}

	/*
	 * if they sent a location and distance - decode lat / lon
	 */
	public function get_my_location() {

		// don't bother unless they entered a location and distance
		if ($this->my_location && $this->distance) {

			// decode address - 3rd party (https://positionstack.com/)
			$ps_client     = new \GuzzleHttp\Client();
			$ps_url        = $this->ps_url . '&query=' . $this->my_location;
			$this->ps_data = $ps_client->request('GET', $ps_url);

			$my_loc_data = json_decode($this->ps_data->getBody()->getContents(),1);

			if (isset($my_loc_data['data'][0]['latitude']) && isset($my_loc_data['data'][0]['longitude']) ) {

				$this->my_lat = $my_loc_data['data'][0]['latitude'];
				$this->my_lon = $my_loc_data['data'][0]['longitude'];

			}
		}

	} // get_my_location

	/*
	 * filter_results (rudimentary)
	 * Currently you can filter by:
	 * type - truck or cart
	 * fooditems - currently just 1 item (e.g. chicken)
	 */
	public function filter_results() {

		$local_result = []; // we return this

		// have we already filtered by location?
		$local_looper = ($this->result) ? $this->result : $this->ft_data;

		foreach ($local_looper as $fdat) {

			$is_match = 0;  // match tracker

			// if they sent a type = match it else match by default
			if ($this->type) {

				if (preg_match("/{$this->type}/i", $fdat['facilitytype'])) {
					$is_match = 1;
				}
			} else {
					$is_match = 1;
			}

			if ($this->items && $is_match) {

				if (preg_match("/{$this->items}/i", $fdat['fooditems'])) {
					$is_match = 1;
				} else {
					$is_match = 0;	
				}
			}

			if ($is_match) {
				$local_result[] = $fdat;
			}
	
		}
	
			$this->result = $local_result;
	}

	/*
	 * if passed a decodable address and a distance get the distance from my_location
	 */
	protected function get_distance(float $ft_lat, float $ft_lon) {

		$unit     = 'miles';
		$theta    = $this->my_lat - $ft_lat;
		$distance = (sin(deg2rad($this->my_lat)) * sin(deg2rad($ft_lat))) + (cos(deg2rad($this->my_lat)) * cos(deg2rad($ft_lat)) * cos(deg2rad($theta))); 

		$distance = acos($distance); 
		$distance = rad2deg($distance); 
		$distance = $distance * 60 * 1.1515; 

		switch($unit) { 
			case 'miles': 
				break; 
			case 'kilometers' : 
				$distance = $distance * 1.609344; 
		} 

		return ($distance <= $this->distance) ? round($distance,2) : 0;
	
	}

	/*
	 * if they passed an address and a distance - filter
	 */
	protected function filter_distance() {

		if ($this->my_lat && $this->my_lon && $this->distance) {

			foreach ($this->ft_data as $fdat) {

				$my_dist = $this->get_distance($fdat['location']['latitude'], $fdat['location']['longitude']);

				 if ($my_dist) {
					 $fdat['my_distance'] = $my_dist;
					 $this->result[]      = $fdat;
				 }
			}
		}
	}
}
