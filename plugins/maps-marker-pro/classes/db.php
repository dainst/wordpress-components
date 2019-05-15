<?php
namespace MMP;

class DB {
	private $wpdb;

	public $layers;
	public $maps;
	public $markers;
	public $rels;

	/**
	 * Sets up the class
	 *
	 * @since 4.0
	 */
	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;

		$this->layers  = "{$wpdb->prefix}mmp_layers";
		$this->maps    = "{$wpdb->prefix}mmp_maps";
		$this->markers = "{$wpdb->prefix}mmp_markers";
		$this->rels    = "{$wpdb->prefix}mmp_relationships";
	}

	/**
	 * Creates the database tables
	 *
	 * @since 4.0
	 */
	public function create_tables() {
		$this->wpdb->query(
			"CREATE TABLE IF NOT EXISTS `{$this->layers}` (
				`id` int(8) NOT NULL AUTO_INCREMENT,
				`wms` int(1) NOT NULL,
				`overlay` int(1) NOT NULL,
				`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`url` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
				`options` text COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
		);
		$this->wpdb->query(
			"CREATE TABLE IF NOT EXISTS `{$this->maps}` (
				`id` int(8) NOT NULL AUTO_INCREMENT,
				`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`settings` text COLLATE utf8_unicode_ci NOT NULL,
				`filters` text COLLATE utf8_unicode_ci NOT NULL,
				`geojson` text COLLATE utf8_unicode_ci NOT NULL,
				`created_by` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`created_on` datetime NOT NULL,
				`updated_by` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`updated_on` datetime NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
		);
		$this->wpdb->query(
			"CREATE TABLE IF NOT EXISTS `{$this->markers}` (
				`id` int(8) NOT NULL AUTO_INCREMENT,
				`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`lat` decimal(10,6) NOT NULL,
				`lng` decimal(10,6) NOT NULL,
				`zoom` decimal(3,1) NOT NULL,
				`icon` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`popup` text COLLATE utf8_unicode_ci NOT NULL,
				`link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`blank` int(1) NOT NULL,
				`created_by` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`created_on` datetime NOT NULL,
				`updated_by` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`updated_on` datetime NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
		);
		$this->wpdb->query(
			"CREATE TABLE IF NOT EXISTS `{$this->rels}` (
				`map_id` int(8) NOT NULL,
				`type_id` int(1) NOT NULL,
				`object_id` int(8) NOT NULL,
				UNIQUE KEY `key` (`map_id`,`type_id`,`object_id`),
				KEY `map_id` (`map_id`),
				KEY `type_id` (`type_id`),
				KEY `object_id` (`object_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
		);
	}

	/**
	 * Deletes the database tables
	 *
	 * @since 4.0
	 */
	public function delete_tables() {
		$this->wpdb->query("DROP TABLE IF EXISTS {$this->layers}");
		$this->wpdb->query("DROP TABLE IF EXISTS {$this->maps}");
		$this->wpdb->query("DROP TABLE IF EXISTS {$this->markers}");
		$this->wpdb->query("DROP TABLE IF EXISTS {$this->rels}");
	}

	/**
	 * Resets the database tables
	 *
	 * @since 4.0
	 */
	public function reset_tables() {
		$this->delete_tables();
		$this->create_tables();
	}

	/**
	 * Returns the total number of maps
	 *
	 * @since 4.0
	 *
	 * @param array $filters The filters to limit results
	 */
	public function count_maps($filters = array()) {
		$filter_query = $this->parse_map_filters($filters);

		$count = $this->wpdb->get_var(
			"SELECT COUNT(1)
			FROM {$this->maps} AS maps
			$filter_query"
		);

		return $count;
	}

	/**
	 * Returns the map for the given ID
	 *
	 * @since 4.0
	 *
	 * @param int $id The map ID
	 * @param bool $count Whether to return marker count
	 */
	public function get_map($id, $count = false) {
		$map = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT maps.*
			FROM {$this->maps} AS maps
			WHERE maps.id = %d",
			$id
		));

		if (!$map) {
			return null;
		}

		if ($count) {
			$map->markers = $this->count_map_markers($map->id);
		}

		return $map;
	}

	/**
	 * Returns the maps for the given IDs
	 *
	 * @since 4.0
	 *
	 * @param array|string $ids The map IDs
	 * @param bool $count Whether to return marker count
	 */
	public function get_maps($ids, $count = false) {
		$ids = $this->sanitize_ids($ids, true);

		$maps = $this->wpdb->get_results(
			"SELECT maps.*
			FROM {$this->maps} AS maps
			WHERE maps.id IN ($ids)"
		);

		if (!$maps) {
			return array();
		}

		if ($count) {
			foreach ($maps as $key => $map) {
				$maps[$key]->markers = $this->count_map_markers($map->id);
			}
		}

		return $maps;
	}

	/**
	 * Returns all maps
	 *
	 * @since 4.0
	 *
	 * @param bool $count Whether to return marker count
	 * @param array $filters The filters to limit results
	 */
	public function get_all_maps($count = false, $filters = array()) {
		$filter_query = $this->parse_map_filters($filters);

		$maps = $this->wpdb->get_results(
			"SELECT maps.*
			FROM {$this->maps} AS maps
			$filter_query"
		);

		if (!$maps) {
			return array();
		}

		if ($count) {
			foreach ($maps as $key => $map) {
				$maps[$key]->markers = $this->count_map_markers($map->id);
			}
		}

		return $maps;
	}

	/**
	 * Returns all posts that use a shortcode for the given map ID
	 *
	 * @since 4.0
	 *
	 * @param int $id The map ID
	 */
	public function get_map_shortcodes($id) {
		$results = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT ID, post_title
			FROM {$this->wpdb->posts}
			WHERE post_status = 'publish' AND (post_content LIKE %s OR post_content LIKE %s)",
			'%[' . $this->wpdb->esc_like(Maps_Marker_Pro::$settings['shortcode']) . '%map="' . $this->wpdb->esc_like($id) . '"%]%',
			'%[' . $this->wpdb->esc_like(Maps_Marker_Pro::$settings['shortcode']) . '%layer="' . $this->wpdb->esc_like($id) . '"%]%'
		));

		if (!$results) {
			return array();
		}

		foreach ($results as $result) {
			$posts[] = array(
				'title' => ($result->post_title) ? esc_html($result->post_title) : esc_html__('(no title)', 'mmp'),
				'link'  => get_permalink($result->ID),
				'edit'  => get_edit_post_link($result->ID)
			);
		}

		return $posts;
	}

	/**
	 * Adds a map
	 *
	 * @since 4.0
	 *
	 * @param object $data The map data to be written
	 * @param int $id The ID for the new map
	 */
	public function add_map($data, $id = 0) {
		$l10n = Maps_Marker_Pro::get_instance('MMP\L10n');

		$insert = $this->wpdb->insert(
			$this->maps,
			array(
				'id' => $id,
				'name' => $data->name,
				'settings' => $data->settings,
				'filters' => $data->filters,
				'geojson' => $data->geojson,
				'created_by' => $data->created_by,
				'created_on' => $data->created_on,
				'updated_by' => $data->updated_by,
				'updated_on' => $data->updated_on
			),
			array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
		);

		if ($insert === false) {
			return false;
		}

		$insert_id = $this->wpdb->insert_id;

		$l10n->register("Map (ID {$insert_id}) name", $data->name);

		return $insert_id;
	}

	/**
	 * Adds multiple maps
	 *
	 * @since 4.0
	 *
	 * @param array $data The map data to be written
	 */
	public function add_maps($data) {
		if (!is_array($data) || !count($data)) {
			return false;
		}

		$cols = implode(',', array_keys($this->prepare_maps()));
		$prep = implode(',', array_values($this->prepare_maps()));
		$sql = "INSERT INTO {$this->maps} ({$cols}) VALUES ";
		foreach ($data as $map) {
			$sql .= $this->wpdb->prepare("({$prep}),", array_values($map));
		}
		$sql = substr($sql, 0, -1); // Remove trailing comma from loop-generated query

		$result = $this->wpdb->query($sql);

		return $result;
	}

	/**
	 * Updates a map
	 *
	 * @since 4.0
	 *
	 * @param object $data The map data to be written
	 * @param int $id The ID of the map to be updated
	 */
	public function update_map($data, $id) {
		$l10n = Maps_Marker_Pro::get_instance('MMP\L10n');

		$update = $this->wpdb->update(
			$this->maps,
			array(
				'name' => $data->name,
				'settings' => $data->settings,
				'filters' => $data->filters,
				'geojson' => $data->geojson,
				'updated_by' => $data->updated_by,
				'updated_on' => $data->updated_on
			),
			array('id' => $id),
			array('%s', '%s', '%s', '%s', '%s', '%s'),
			array('%d')
		);

		if ($update === false) {
			return false;
		}

		$l10n->register("Map (ID {$id}) name", $data->name);

		return $update;
	}

	/**
	 * Updates multiple maps
	 *
	 * @since 4.0
	 *
	 * @param object $data The map data to be written
	 * @param int $ids The IDs of the maps to be updated
	 */
	public function update_maps($data, $ids) {
		$l10n = Maps_Marker_Pro::get_instance('MMP\L10n');

		$ids = $this->sanitize_ids($ids);

		$rows = 0;
		foreach ($ids as $id) {
			$update = $this->wpdb->update(
				$this->maps,
				array(
					'name' => $data->name,
					'settings' => $data->settings,
					'filters' => $data->filters,
					'geojson' => $data->geojson,
					'updated_by' => $data->updated_by,
					'updated_on' => $data->updated_on
				),
				array('id' => $id),
				array('%s', '%s', '%s', '%s', '%s', '%s'),
				array('%d')
			);

			if ($update) {
				$l10n->register("Map (ID {$id}) name", $data->name);
				$rows += $update;
			}
		}

		return $rows;
	}

	/**
	 * Deletes a map and its relationships
	 *
	 * @since 4.0
	 *
	 * @param int $id The ID of the map to be deleted
	 */
	public function delete_map($id) {
		$delete = $this->wpdb->delete(
			$this->maps,
			array('id' => $id),
			array('%d')
		);
		$this->wpdb->delete(
			$this->rels,
			array('map_id' => $id),
			array('%d')
		);

		return $delete;
	}

	/**
	 * Deletes multiple maps and their relationships
	 *
	 * @since 4.0
	 *
	 * @param int $ids The IDs of the maps to be deleted
	 */
	public function delete_maps($ids) {
		$ids = $this->sanitize_ids($ids);

		$results = $this->wpdb->query(
			"DELETE FROM {$this->maps}
			WHERE `id` IN ($ids)"
		);
		$this->wpdb->query(
			"DELETE FROM {$this->rels}
			WHERE `map_id` IN ($ids)"
		);

		return $results;
	}

	/**
	 * Returns the total number of markers
	 *
	 * @since 4.0
	 *
	 * @param array $filters The filters to limit results
	 */
	public function count_markers($filters = array()) {
		$filter_query = $this->parse_marker_filters($filters, false);

		$count = $this->wpdb->get_var(
			"SELECT COUNT(1)
			FROM {$this->markers} AS markers
			LEFT JOIN {$this->rels} AS rels ON (rels.object_id = markers.id AND rels.type_id = 2)
			$filter_query"
		);

		return $count;
	}

	/**
	 * Returns the total number of markers for the given map ID
	 *
	 * @since 4.0
	 *
	 * @param int $id The map ID
	 */
	public function count_map_markers($id) {
		$map = $this->get_map($id);

		if (!$map) {
			return null;
		}

		$settings = json_decode($map->settings);
		if ($settings->filtersAllMarkers) {
			$count = $this->count_markers();
		} else {
			$filters = json_decode($map->filters, true);
			$ids = $this->sanitize_ids(array_merge(array($map->id), array_keys($filters)), true);
			$count = $this->wpdb->get_var(
				"SELECT COUNT(1)
				FROM {$this->markers} AS markers
				JOIN {$this->rels} AS rels ON (rels.map_id IN ($ids) AND rels.type_id = 2 AND rels.object_id = markers.id)"
			);
		}

		return $count;
	}

	/**
	 * Returns the marker for the given ID
	 *
	 * @since 4.0
	 *
	 * @param int $id The marker ID
	 */
	public function get_marker($id) {
		$marker = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT markers.*, GROUP_CONCAT(rels.map_id) AS maps
			FROM {$this->markers} AS markers
			LEFT JOIN {$this->rels} AS rels ON (rels.type_id = 2 AND rels.object_id = markers.id)
			WHERE markers.id = %d
			GROUP BY markers.id",
			$id
		));

		if (!$marker) {
			return null;
		}

		return $marker;
	}

	/**
	 * Returns all markers for the given IDs
	 *
	 * @since 4.0
	 *
	 * @param array|string $ids The marker IDs
	 */
	public function get_markers($ids) {
		$ids = $this->sanitize_ids($ids, true);

		$markers = $this->wpdb->get_results(
			"SELECT markers.*, GROUP_CONCAT(rels.map_id) AS maps
			FROM {$this->markers} AS markers
			LEFT JOIN {$this->rels} AS rels ON (rels.type_id = 2 AND rels.object_id = markers.id)
			WHERE markers.id IN ($ids)
			GROUP BY markers.id"
		);

		if (!$markers) {
			return array();
		}

		return $markers;
	}

	/**
	 * Returns all markers
	 *
	 * @since 4.0
	 *
	 * @param array $filters The filters to limit results
	 */
	public function get_all_markers($filters = array()) {
		if (isset($filters['radius']) && isset($filters['lat']) && isset($filters['lng'])) {
			$earth_radius = (isset($filters['unit']) && $filters['unit'] === 'imperial') ? 3959000 : 6371000;
			$lat = floatval($filters['lat']);
			$lng = floatval($filters['lng']);
			$distance = ", $earth_radius * ACOS(COS(RADIANS($lat)) * COS(RADIANS(markers.lat)) * COS(RADIANS(markers.lng) - RADIANS($lng)) + SIN(RADIANS($lat)) * SIN(RADIANS(markers.lat))) AS distance";
		} else {
			$distance = '';
		}

		$filter_query = $this->parse_marker_filters($filters);

		$markers = $this->wpdb->get_results(
			"SELECT markers.*, GROUP_CONCAT(rels.map_id) AS maps $distance
			FROM {$this->markers} AS markers
			LEFT JOIN {$this->rels} AS rels ON (rels.object_id = markers.id AND rels.type_id = 2)
			$filter_query"
		);

		if (!$markers) {
			return array();
		}

		return $markers;
	}

	/**
	 * Returns all markers for the given map ID
	 *
	 * @since 4.0
	 *
	 * @param int $id The map ID
	 */
	public function get_map_markers($id) {
		$markers = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT markers.*, GROUP_CONCAT(maps.id) AS maps
			FROM {$this->markers} AS markers
			JOIN {$this->rels} AS rels ON (rels.map_id = %d AND rels.type_id = 2 AND rels.object_id = markers.id)
			JOIN {$this->maps} AS maps ON (rels.map_id = maps.id)
			GROUP BY markers.id",
			$id
		));

		if (!$markers) {
			return array();
		}

		return $markers;
	}

	/**
	 * Returns all markers for the given map IDs
	 *
	 * @since 4.0
	 *
	 *
	 * @param array|string $ids The map IDs
	 */
	public function get_maps_markers($ids) {
		$ids = $this->sanitize_ids($ids, true);

		if (!$ids) {
			return array();
		}

		$markers = $this->wpdb->get_results(
			"SELECT markers.*, GROUP_CONCAT(maps.id) AS maps
			FROM {$this->markers} AS markers
			JOIN {$this->rels} AS rels ON (rels.map_id IN ($ids) AND rels.type_id = 2 AND rels.object_id = markers.id)
			JOIN {$this->maps} AS maps ON (rels.map_id = maps.id)
			GROUP BY markers.id"
		);

		if (!$markers) {
			return array();
		}

		return $markers;
	}

	/**
	 * Adds a marker
	 *
	 * @since 4.0
	 *
	 * @param object $data The marker data to be written
	 * @param bool $geocode Whether to get lat/lng via geocoding
	 * @param int $id The ID for the new marker
	 */
	public function add_marker($data, $geocode = false, $id = 0) {
		$geocoding = Maps_Marker_Pro::get_instance('MMP\Geocoding');
		$l10n = Maps_Marker_Pro::get_instance('MMP\L10n');

		if ($geocode) {
			if (!$data->address) {
				return null;
			}
			$result = $geocoding->getLatLng($data->address);
			if (!$result['success']) {
				return null;
			}
			$data->lat = $result['lat'];
			$data->lng = $result['lon'];
		}

		$insert = $this->wpdb->insert(
			$this->markers,
			array(
				'id' => $id,
				'name' => $data->name,
				'address' => $data->address,
				'lat' => $data->lat,
				'lng' => $data->lng,
				'zoom' => $data->zoom,
				'icon' => $data->icon,
				'popup' => $data->popup,
				'link' => $data->link,
				'blank' => $data->blank,
				'created_by' => $data->created_by,
				'created_on' => $data->created_on,
				'updated_by' => $data->updated_by,
				'updated_on' => $data->updated_on
			),
			array('%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
		);

		if ($insert === false) {
			return false;
		}

		$insert_id = $this->wpdb->insert_id;

		$l10n->register("Marker (ID {$insert_id}) name", $data->name);
		$l10n->register("Marker (ID {$insert_id}) address", $data->address);
		$l10n->register("Marker (ID {$insert_id}) popup", $data->popup);

		return $insert_id;
	}

	/**
	 * Adds multiple markers
	 *
	 * @since 4.0
	 *
	 * @param array $data The marker data to be written
	 * @param bool $geocode Whether to get lat/lng via geocoding
	 */
	public function add_markers($data, $geocode = false) {
		$geocoding = Maps_Marker_Pro::get_instance('MMP\Geocoding');

		if (!is_array($data) || !count($data)) {
			return false;
		}

		$cols = implode(',', array_keys($this->prepare_markers()));
		$prep = implode(',', array_values($this->prepare_markers()));
		$sql = "INSERT INTO {$this->markers} ({$cols}) VALUES ";
		foreach ($data as $marker) {
			$sql .= $this->wpdb->prepare("({$prep}),", array_values($marker));
		}
		$sql = substr($sql, 0, -1); // Remove trailing comma from loop-generated query

		$result = $this->wpdb->query($sql);

		return $result;
	}

	/**
	 * Updates a marker
	 *
	 * @since 4.0
	 *
	 * @param object $data The marker data to be written
	 * @param int $id The ID of the marker to be updated
	 * @param bool $geocode Whether to get lat/lng via geocoding
	 */
	public function update_marker($data, $id, $geocode = false) {
		$geocoding = Maps_Marker_Pro::get_instance('MMP\Geocoding');
		$l10n = Maps_Marker_Pro::get_instance('MMP\L10n');

		if ($geocode) {
			if (!$data->address) {
				return null;
			}
			$result = $geocoding->getLatLng($data->address);
			if (!$result['success']) {
				return null;
			}
			$data->lat = $result['lat'];
			$data->lng = $result['lon'];
		}

		$update = $this->wpdb->update(
			$this->markers,
			array(
				'name' => $data->name,
				'address' => $data->address,
				'lat' => $data->lat,
				'lng' => $data->lng,
				'zoom' => $data->zoom,
				'icon' => $data->icon,
				'popup' => $data->popup,
				'link' => $data->link,
				'blank' => $data->blank,
				'updated_by' => $data->updated_by,
				'updated_on' => $data->updated_on
			),
			array('id' => $id),
			array('%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%s'),
			array('%d')
		);

		if ($update === false) {
			return false;
		}

		$l10n->register("Marker (ID {$id}) name", $data->name);
		$l10n->register("Marker (ID {$id}) address", $data->address);
		$l10n->register("Marker (ID {$id}) popup", $data->popup);

		return $update;
	}

	/**
	 * Updates multiple markers
	 *
	 * @since 4.0
	 *
	 * @param object $data The marker data to be written
	 * @param int $ids The IDs of the markers to be updated
	 * @param bool $geocode Whether to get lat/lng via geocoding
	 */
	public function update_markers($data, $ids, $geocode = false) {
		$geocoding = Maps_Marker_Pro::get_instance('MMP\Geocoding');
		$l10n = Maps_Marker_Pro::get_instance('MMP\L10n');

		$ids = $this->sanitize_ids($ids);

		if ($geocode) {
			if (!$data->address) {
				return null;
			}
			$result = $geocoding->getLatLng($data->address);
			if (!$result['success']) {
				return null;
			}
			$data->lat = $result['lat'];
			$data->lng = $result['lon'];
		}

		$rows = 0;
		foreach ($ids as $id) {
			$update = $this->wpdb->update(
				$this->markers,
				array(
					'name' => $data->name,
					'address' => $data->address,
					'lat' => $data->lat,
					'lng' => $data->lng,
					'zoom' => $data->zoom,
					'icon' => $data->icon,
					'popup' => $data->popup,
					'link' => $data->link,
					'blank' => $data->blank,
					'updated_by' => $data->updated_by,
					'updated_on' => $data->updated_on
				),
				array('id' => $id),
				array('%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%s'),
				array('%d')
			);

			if ($update) {
				$l10n->register("Marker (ID {$id}) name", $data->name);
				$l10n->register("Marker (ID {$id}) address", $data->address);
				$l10n->register("Marker (ID {$id}) popup", $data->popup);
				$rows += $update;
			}
		}

		return $rows;
	}

	/**
	 * Assigns a marker to a map
	 *
	 * @since 4.0
	 *
	 * @param int $map_id The map ID
	 * @param int $marker_id The marker ID
	 */
	public function assign_marker($map_id, $marker_id) {
		$map_id = absint($map_id);
		$marker_id = absint($marker_id);

		if (!$map_id || !$marker_id) {
			return false;
		}

		$assign = $this->wpdb->query($this->wpdb->prepare(
			"INSERT IGNORE INTO {$this->rels} (map_id, type_id, object_id)
			VALUES ('%d', '%d', '%d')",
			$map_id, 2, $marker_id
		));

		return $assign;
	}

	/**
	 * Assigns multiple markers to a map
	 *
	 * @since 4.0
	 *
	 * @param int $map_id The map ID
	 * @param int $marker_ids The marker IDs
	 */
	public function assign_markers($map_id, $marker_ids) {
		$marker_ids = $this->sanitize_ids($marker_ids);

		if (!count($marker_ids)) {
			return false;
		}

		$cols = implode(',', array_keys($this->prepare_rels()));
		$prep = implode(',', array_values($this->prepare_rels()));
		$sql = "INSERT INTO {$this->rels} ({$cols}) VALUES ";
		foreach ($marker_ids as $marker_id) {
			$sql .= $this->wpdb->prepare("({$prep}),", $map_id, 2, $marker_id);
		}
		$sql = substr($sql, 0, -1); // Remove trailing comma from loop-generated query

		$result = $this->wpdb->query($sql);

		return $result;
	}

	/**
	 * Assigns a marker to multiple maps
	 *
	 * @since 4.0
	 *
	 * @param int $map_ids The map IDs
	 * @param int $marker_id The marker ID
	 */
	public function assign_maps_marker($map_ids, $marker_id) {
		$map_ids = $this->sanitize_ids($map_ids);

		if (!count($map_ids)) {
			return false;
		}

		$cols = implode(',', array_keys($this->prepare_rels()));
		$prep = implode(',', array_values($this->prepare_rels()));
		$sql = "INSERT INTO {$this->rels} ({$cols}) VALUES ";
		foreach ($map_ids as $map_id) {
			$sql .= $this->wpdb->prepare("({$prep}),", $map_id, 2, $marker_id);
		}
		$sql = substr($sql, 0, -1); // Remove trailing comma from loop-generated query

		$result = $this->wpdb->query($sql);

		return $result;
	}

	/**
	 * Assigns multiple markers to multiple maps
	 *
	 * @since 4.0
	 *
	 * @param int $map_ids The map IDs
	 * @param int $marker_ids The marker IDs
	 */
	public function assign_maps_markers($map_ids, $marker_ids) {
		$map_ids = $this->sanitize_ids($map_ids);
		$marker_ids = $this->sanitize_ids($marker_ids);

		if (!count($map_ids) || !count($marker_ids)) {
			return false;
		}

		$cols = implode(',', array_keys($this->prepare_rels()));
		$prep = implode(',', array_values($this->prepare_rels()));
		$sql = "INSERT INTO {$this->rels} ({$cols}) VALUES ";
		foreach ($map_ids as $map_id) {
			foreach ($marker_ids as $marker_id) {
				$sql .= $this->wpdb->prepare("({$prep}),", $map_id, 2, $marker_id);
			}
		}
		$sql = substr($sql, 0, -1); // Remove trailing comma from loop-generated query

		$result = $this->wpdb->query($sql);

		return $result;
	}

	/**
	 * Unassigns a marker from a map
	 *
	 * @since 4.0
	 *
	 * @param int $map_id The map ID
	 * @param int $marker_id The marker ID
	 */
	public function unassign_marker($map_id, $marker_id) {
		$delete = $this->wpdb->delete(
			$this->rels,
			array('map_id' => $map_id, 'type_id' => 2, 'object_id' => $marker_id),
			array('%d', '%d', '%d')
		);

		return $delete;
	}

	/**
	 * Unassigns multiple markers from a map
	 *
	 * @since 4.0
	 *
	 * @param int $map_id The map ID
	 * @param int $marker_ids The marker IDs
	 */
	public function unassign_markers($map_id, $marker_ids) {
		$marker_ids = $this->sanitize_ids($marker_ids, true);

		$results = $this->wpdb->query(
			"DELETE FROM {$this->rels}
			WHERE `map_id` = $map_id AND `type_id` = 2 AND `object_id` IN ($marker_ids)"
		);

		return $results;
	}

	/**
	 * Unassigns a marker from multiple maps
	 *
	 * @since 4.0
	 *
	 * @param int $map_ids The map IDs
	 * @param int $marker_id The marker ID
	 */
	public function unassign_maps_marker($map_ids, $marker_id) {
		$map_ids = $this->sanitize_ids($map_ids, true);

		$results = $this->wpdb->query(
			"DELETE FROM {$this->rels}
			WHERE `map_id` IN ($map_ids) AND `type_id` = 2 AND `object_id` = $marker_id"
		);

		return $results;
	}

	/**
	 * Unassigns all markers from a map
	 *
	 * @since 4.0
	 *
	 * @param int $map_id The map ID
	 */
	public function unassign_all_markers($map_id) {
		$this->wpdb->delete(
			$this->rels,
			array('map_id' => $map_id, 'type_id' => 2),
			array('%d', '%d')
		);
	}

	/**
	 * Deletes a marker and its relationships
	 *
	 * @since 4.0
	 *
	 * @param int $id The marker ID
	 */
	public function delete_marker($id) {
		$this->wpdb->delete(
			$this->markers,
			array('id' => $id),
			array('%d')
		);
		$this->wpdb->delete(
			$this->rels,
			array('type_id' => 2, 'object_id' => $id),
			array('%d', '%d')
		);
	}

	/**
	 * Deletes multiple markers and their relationships
	 *
	 * @since 4.0
	 *
	 * @param int $ids The marker IDs
	 */
	public function delete_markers($ids) {
		$ids = $this->sanitize_ids($ids, true);

		$results = $this->wpdb->query(
			"DELETE FROM {$this->markers}
			WHERE `id` IN ($ids)"
		);
		$this->wpdb->query(
			"DELETE FROM {$this->rels}
			WHERE `type_id` = 2 AND `object_id` IN ($ids)"
		);

		return $results;
	}

	/**
	 * Returns the layer for the given ID
	 *
	 * @since 4.0
	 *
	 * @param int $id The layer ID
	 */
	public function get_layer($id) {
		$layer = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT layers.*
			FROM {$this->layers} AS layers
			WHERE layers.id = %d",
			$id
		));

		if (!$layer) {
			return null;
		}

		return $layer;
	}

	/**
	 * Returns all layers
	 *
	 * @since 4.0
	 */
	public function get_all_layers() {
		$layers = $this->wpdb->get_results(
			"SELECT layers.*
			FROM {$this->layers} AS layers"
		);

		if (!$layers) {
			return array();
		}

		return $layers;
	}

	/**
	 * Returns all basemaps
	 *
	 * @since 4.0
	 */
	public function get_all_basemaps() {
		$basemaps = $this->wpdb->get_results(
			"SELECT layers.*
			FROM {$this->layers} AS layers
			WHERE layers.overlay = 0"
		);

		if (!$basemaps) {
			return array();
		}

		return $basemaps;
	}

	/**
	 * Returns all overlays
	 *
	 * @since 4.0
	 */
	public function get_all_overlays() {
		$overlays = $this->wpdb->get_results(
			"SELECT layers.*
			FROM {$this->layers} AS layers
			WHERE layers.overlay = 1"
		);

		if (!$overlays) {
			return array();
		}

		return $overlays;
	}

	/**
	 * Adds a layer
	 *
	 * @since 4.0
	 *
	 * @param object $data The layer data to be written
	 * @param int $id The ID for the new layer
	 */
	public function add_layer($data, $id = 0) {
		$insert = $this->wpdb->insert(
			$this->layers,
			array(
				'id' => $id,
				'wms' => $data->wms,
				'overlay' => $data->overlay,
				'name' => $data->name,
				'url' => $data->url,
				'options' => $data->options
			),
			array('%d', '%d', '%d', '%s', '%s', '%s')
		);

		if ($insert === false) {
			return false;
		}

		return $this->wpdb->insert_id;
	}

	/**
	 * Updates a layer
	 *
	 * @since 4.0
	 *
	 * @param object $data The layer data to be written
	 * @param int $id The ID of the layer to be updated
	 */
	public function update_layer($data, $id) {
		$update = $this->wpdb->update(
			$this->layers,
			array(
				'wms' => $data->wms,
				'overlay' => $data->overlay,
				'name' => $data->name,
				'url' => $data->url,
				'options' => $data->options
			),
			array('id' => $id),
			array('%d', '%d', '%s', '%s', '%s'),
			array('%d')
		);

		return $update;
	}

	/**
	 * Deletes a layer
	 *
	 * @since 4.0
	 *
	 * @param int $id The layer ID
	 */
	public function delete_layer($id) {
		$delete = $this->wpdb->delete(
			$this->layers,
			array('id' => $id),
			array('%d')
		);

		return $delete;
	}

	/**
	 * Sanitizes an array or comma-separated list of IDs
	 *
	 * @since 4.0
	 *
	 * @param array|string $ids The IDs to sanitize
	 * @param bool $csv Whether to return the result as a CSV string
	 */
	public function sanitize_ids($ids, $csv = false) {
		if (!is_array($ids)) {
			$ids = explode(',', $ids);
		}

		$ids = array_map('absint', $ids);
		$ids = array_unique($ids);
		$ids = array_filter($ids);

		natsort($ids);

		if ($csv) {
			$ids = implode(',', $ids);
		}

		return $ids;
	}

	/**
	 * Returns the layers table mapping for prepare statements
	 *
	 * @since 4.0
	 */
	public function prepare_layers() {
		$cols = array(
			'id' => '%d',
			'wms' => '%d',
			'overlay' => '%d',
			'name' => '%s',
			'url' => '%s',
			'options' => '%s'
		);

		return $cols;
	}

	/**
	 * Returns the maps table mapping for prepare statements
	 *
	 * @since 4.0
	 */
	public function prepare_maps() {
		$cols = array(
			'id' => '%d',
			'name' => '%s',
			'settings' => '%s',
			'filters' => '%s',
			'geojson' => '%s',
			'created_by' => '%s',
			'created_on' => '%s',
			'updated_by' => '%s',
			'updated_on' => '%s'
		);

		return $cols;
	}

	/**
	 * Returns the markers table mapping for prepare statement
	 *
	 * @since 4.0
	 */
	public function prepare_markers() {
		$cols = array(
			'id' => '%d',
			'name' => '%s',
			'address' => '%s',
			'lat' => '%f',
			'lng' => '%f',
			'zoom' => '%f',
			'icon' => '%s',
			'popup' => '%s',
			'link' => '%s',
			'blank' => '%d',
			'created_by' => '%s',
			'created_on' => '%s',
			'updated_by' => '%s',
			'updated_on' => '%s'
		);

		return $cols;
	}

	/**
	 * Returns the relationships table mapping for prepare statement
	 *
	 * @since 4.0
	 */
	public function prepare_rels() {
		$cols = array(
			'map_id' => '%d',
			'type_id' => '%d',
			'object_id' => '%d'
		);

		return $cols;
	}

	/**
	 * Parses filters for map queries
	 *
	 * @since 4.0
	 *
	 * @param array $filters The map filters
	 */
	private function parse_map_filters($filters) {
		$query = 'WHERE 1';
		if (isset($filters['exclude'])) {
			$filters['exclude'] = $this->sanitize_ids($filters['exclude'], true);
			if ($filters['exclude']) {
				$query .= " AND maps.id NOT IN ({$filters['exclude']})";
			}
		}
		if (isset($filters['include'])) {
			$filters['include'] = $this->sanitize_ids($filters['include'], true);
			if ($filters['include']) {
				$query .= " AND maps.id IN ({$filters['include']})";
			}
		}
		if (isset($filters['name'])) {
			$query .= $this->wpdb->prepare(" AND maps.name LIKE '%%%s%%'", $filters['name']);
		}
		if (isset($filters['created_by'])) {
			$query .= $this->wpdb->prepare(" AND maps.created_by LIKE '%%%s%%'", $filters['created_by']);
		}
		if (isset($filters['updated_by'])) {
			$query .= $this->wpdb->prepare(" AND maps.updated_by LIKE '%%%s%%'", $filters['updated_by']);
		}
		if (isset($filters['orderby']) && array_key_exists($filters['orderby'], $this->prepare_maps())) {
			$query .= " ORDER BY {$filters['orderby']} ";
			$query .= (isset($filters['sortorder']) && $filters['sortorder'] === 'desc') ? 'DESC' : 'ASC';
		}
		if (isset($filters['limit'])) {
			$query .= ' LIMIT ' . absint($filters['limit']);
		}
		if (isset($filters['offset'])) {
			$query .= ' OFFSET ' . absint($filters['offset']);
		}

		return $query;
	}

	/**
	 * Parses filters for marker queries
	 *
	 * @since 4.0
	 *
	 * @param array $filters The marker filters
	 * @param bool $group Whether to add the GROUP BY argument
	 */
	private function parse_marker_filters($filters, $group = true) {
		$query = 'WHERE 1';
		if (isset($filters['exclude'])) {
			$filters['exclude'] = $this->sanitize_ids($filters['exclude'], true);
			if ($filters['exclude']) {
				$query .= " AND markers.id NOT IN ({$filters['exclude']})";
			}
		}
		if (isset($filters['include'])) {
			$filters['include'] = $this->sanitize_ids($filters['include'], true);
			if ($filters['include']) {
				$query .= " AND markers.id IN ({$filters['include']})";
			}
		}
		if (isset($filters['exclude_maps'])) {
			$filters['exclude_maps'] = $this->sanitize_ids($filters['exclude_maps'], true);
			if ($filters['exclude_maps']) {
				$query .= " AND rels.map_id NOT IN ({$filters['exclude_maps']})";
			}
		}
		if (isset($filters['include_maps'])) {
			$filters['include_maps'] = $this->sanitize_ids($filters['include_maps'], true);
			if ($filters['include_maps']) {
				$query .= " AND rels.map_id IN ({$filters['include_maps']})";
			}
		}
		if (isset($filters['contains'])) {
			$query .= $this->wpdb->prepare(" AND (markers.name LIKE '%%%s%%' OR markers.address LIKE '%%%s%%' OR markers.popup LIKE '%%%s%%')", $filters['contains'], $filters['contains'], $filters['contains']);
		}
		if (isset($filters['name'])) {
			$query .= $this->wpdb->prepare(" AND markers.name LIKE '%%%s%%'", $filters['name']);
		}
		if (isset($filters['address'])) {
			$query .= $this->wpdb->prepare(" AND markers.address LIKE '%%%s%%'", $filters['address']);
		}
		if (isset($filters['popup'])) {
			$query .= $this->wpdb->prepare(" AND markers.popup LIKE '%%%s%%'", $filters['popup']);
		}
		if (isset($filters['created_by'])) {
			$query .= $this->wpdb->prepare(" AND markers.created_by LIKE '%%%s%%'", $filters['created_by']);
		}
		if (isset($filters['updated_by'])) {
			$query .= $this->wpdb->prepare(" AND markers.updated_by LIKE '%%%s%%'", $filters['updated_by']);
		}
		if ($group) {
			$query .= ' GROUP BY markers.id';
		}
		if (isset($filters['orderby']) && array_key_exists($filters['orderby'], $this->prepare_markers())) {
			$query .= " ORDER BY {$filters['orderby']} ";
			$query .= (isset($filters['sortorder']) && $filters['sortorder'] === 'desc') ? 'DESC' : 'ASC';
		}
		if (isset($filters['limit'])) {
			$query .= ' LIMIT ' . absint($filters['limit']);
		}
		if (isset($filters['offset'])) {
			$query .= ' OFFSET ' . absint($filters['offset']);
		}
		if (isset($filters['radius']) && isset($filters['lat']) && isset($filters['lng'])) {
			$query .= ' HAVING distance <= ' . absint($filters['radius']);
		}

		return $query;
	}
}
