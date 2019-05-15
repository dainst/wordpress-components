<?php
namespace MMP;

class Layar {
	/**
	 * Processes the Layar request
	 *
	 * @since 4.0
	 */
	public function request() {
		$db = Maps_Marker_Pro::get_instance('MMP\DB');

		$request['userId'] = (isset($_GET['userId'])) ? $_GET['userId'] : null;
		$request['layerName'] = (isset($_GET['layerName'])) ? $_GET['layerName'] : null;
		$request['version'] = (isset($_GET['version'])) ? $_GET['version'] : null;
		$request['lat'] = (isset($_GET['lat'])) ? $_GET['lat'] : null;
		$request['lon'] = (isset($_GET['lon'])) ? $_GET['lon'] : null;
		$request['countryCode'] = (isset($_GET['countryCode'])) ? $_GET['countryCode'] : null;
		$request['lang'] = (isset($_GET['lang'])) ? $_GET['lang'] : null;
		$request['action'] = (isset($_GET['action']) && $_GET['action'] === 'update') ? 'update' : 'refresh';
		$request['radius'] = (isset($_GET['radius'])) ? $_GET['radius'] : Maps_Marker_Pro::$settings['layarRadius'];
		$request['CHECKBOXLIST'] = (isset($_GET['CHECKBOXLIST'])) ? $_GET['CHECKBOXLIST'] : null;
		$request['SEARCHBOX'] = (isset($_GET['SEARCHBOX'])) ? $_GET['SEARCHBOX'] : null;

		foreach ($request as $param => $value) {
			if (!$value && $param !== 'CHECKBOXLIST' && $param !== 'SEARCHBOX') {
				$this->error(20, $request['layerName'], $param);
			}
		}

		$markers = $db->get_all_markers(array(
			'include_maps' => $request['CHECKBOXLIST'],
			'radius' => $request['radius'],
			'lat' => $request['lat'],
			'lng' => $request['lon'],
			'units' => (Maps_Marker_Pro::$settings['layarUnits'] === 'miles') ? 'imperial' : 'metric',
			'name' => $request['SEARCHBOX'],
			'popup' => $request['SEARCHBOX']
		));

		if (!count($markers)) {
			$this->error(21, $request['layerName']);
		}

		$output = $this->get_json($request, $markers);
		$this->response($output);
	}

	/**
	 * Resolves and displays errors
	 *
	 * @since 4.0
	 *
	 * @param int $error The error ID
	 * @param string $layer The Layer that triggered the error
	 * @param string $param Optional missing parameter that caused an error
	 */
	private function error($error, $layer, $param = null) {
		$error_codes = array(
			20 => "Required parameter missing: $param",
			21 => 'No markers found. Please adjust the filter settings.'
		);

		$json['layer'] = $layer;
		$json['hotspots'] = array();
		$json['errorCode'] = $error;
		$json['errorString'] = (isset($error_codes[$error])) ? $error_codes[$error] : 'Error';

		die($this->response(json_encode($json, JSON_PRETTY_PRINT)));
	}

	/**
	 * Converts data into the JSON format
	 *
	 * @since 4.0
	 *
	 * @param array $request The request parameters
	 * @param object $markers The markers object
	 */
	private function get_json($request, $markers) {
		$json['layer'] = $request['layerName'];
		$json['hotspots'] = array();
		foreach ($markers as $marker) {
			if (Maps_Marker_Pro::$settings['layarIcons'] === 'mapsmarker') {
				$marker->icon = ($marker->icon) ? Maps_Marker_Pro::$icons_url . $marker->icon : plugins_url('images/leaflet/marker.png', __DIR__);
				$marker->icon_type = 0;
			} else if (Maps_Marker_Pro::$settings['layarIcons'] === 'layar_standard') {
				$marker->icon = null;
				$marker->icon_type = 0;
			} else if (Maps_Marker_Pro::$settings['layarIcons'] === 'layar_custom') {
				$marker->icon = null;
				$marker->icon_type = 1;
			}
			$json['hotspots'][] = array(
				'id' => $marker->id,
				'anchor' => array(
					'geolocation' => array(
						'lat' => $marker->lat,
						'lon' => $marker->lng
					)
				),
				'text' => array(
					'title' => $marker->name,
					'description' => wp_kses($marker->popup, array()),
					'footnote' => Maps_Marker_Pro::$settings['layarFootnote']
				),
				'imageURL' => ($marker->icon) ? Maps_Marker_Pro::$icons_url . $marker->icon : plugins_url('images/leaflet/marker.png', __DIR__),
				'icon' => array(
					'url' => $marker->icon,
					'type' => $marker->icon_type
				)
			);
		}
		$json['errorCode'] = 0;
		$json['errorString'] = 'OK';
		$json['radius'] = $request['radius'];
		$json['refreshInterval'] = Maps_Marker_Pro::$settings['layarRefreshInterval'];
		$json['refreshDistance'] = Maps_Marker_Pro::$settings['layarRefreshDistance'];
		$json['fullRefresh'] = ($request['action'] === 'refresh');

		return json_encode($json, JSON_PRETTY_PRINT);
	}

	/**
	 * Outputs the response
	 *
	 * @since 4.0
	 *
	 * @param string $output The contents of the response
	 */
	private function response($output) {
		header('Access-Control-Allow-Origin: *');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json; charset=utf-8');

		echo $output;
	}
}
