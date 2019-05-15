<?php
namespace MMP;

class Update {
	private $page;

	/**
	 * Sets up the class
	 *
	 * @since 4.0
	 */
	public function __construct() {
		$this->page = isset($_GET['page']) ? $_GET['page'] : null;
	}

	/**
	 * Registers the hooks
	 *
	 * @since 4.0
	 */
	public function init() {
		add_filter('puc_check_now-maps-marker-pro', array($this, 'puc_update_check'));

		add_action('init', array($this, 'update'));
		add_action('all_admin_notices', array($this, 'check'));
		add_action('all_admin_notices', array($this, 'changelog'));
		add_action('wp_ajax_mmp_dismiss_changelog', array($this, 'dismiss_changelog'));
	}

	/**
	 * Checks for a valid license before looking for updates
	 *
	 * @since 4.0
	 *
	 * @param bool $check Whether a check for updates would occur
	 */
	public function puc_update_check($check) {
		$spbas = Maps_Marker_Pro::get_instance('MMP\SPBAS');

		if ($check !== false) {
			$check = $spbas->check_for_updates();
		}

		return $check;
	}

	/**
	 * Executes update routines
	 *
	 * @since 4.0
	 */
	public function update() {
		global $wpdb;
		$db = Maps_Marker_Pro::get_instance('MMP\DB');
		$setup = Maps_Marker_Pro::get_instance('MMP\Setup');

		$version = get_option('mapsmarkerpro_version');
		if (!$version || $version === Maps_Marker_Pro::$version) {
			return;
		}

		// No break statements, so everything runs consecutively
		switch ($version) {
			case '4.2':
				$wpdb->query(
					"ALTER TABLE `{$db->layers}`
					CHANGE `url` `url` VARCHAR(2048) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"
				);
				$wpdb->query(
					"ALTER TABLE `{$db->maps}`
					ADD `geojson` TEXT NOT NULL AFTER `filters`"
				);
				$wpdb->query(
					"ALTER TABLE `{$db->markers}`
					ADD `blank` INT(1) NOT NULL AFTER `link`"
				);
		}

		$setup->setup();

		update_option('mapsmarkerpro_version', Maps_Marker_Pro::$version);
		update_option('mapsmarkerpro_changelog', $version);
		update_option('mapsmarkerpro_key_local', null);
	}

	/**
	 * Checks whether an update is available
	 *
	 * @since 4.0
	 */
	public function check() {
		global $pagenow;
		$spbas = Maps_Marker_Pro::get_instance('MMP\SPBAS');
		$l10n = Maps_Marker_Pro::get_instance('MMP\L10n');

		if ((strpos($this->page, 'mapsmarkerpro') === false || $this->page === 'mapsmarkerpro_license') && $pagenow !== 'plugins.php') {
			return;
		}

		if ($spbas->check_for_updates()) {
			$update_plugins = get_site_transient('update_plugins');
			if (isset($plugin_updates->response[Maps_Marker_Pro::$file]->new_version)) {
				$new_version = $update_plugins->response[Maps_Marker_Pro::$file]->new_version;
				?>
				<div class="notice notice-warning">
					<p>
						<strong><?= esc_html__('Maps Marker Pro - plugin update available!', 'mmp') ?></strong><br />
						<?= sprintf($l10n->kses__('You are currently using v%1$s and the plugin author highly recommends updating to v%2$s for new features, bugfixes and updated translations (please see <a href="%3$s" target="_blank">this blog post</a> for more details about the latest release).', 'mmp'), Maps_Marker_Pro::$version, $new_version, "https://mapsmarker.com/v{$new_version}p") ?><br />
						<?php if (current_user_can('update_plugins')): ?>
							<?= sprintf($l10n->kses__('Update instruction: please start the update from the <a href="%1$s">updates page</a>.', 'mmp'), get_admin_url(null, 'update-core.php')) ?>
						<?php else: ?>
							<?= sprintf($l10n->kses__('Update instruction: as your user does not have the right to update plugins, please contact your <a href="%1$s">administrator</a>', 'mmp'), 'mailto:' . get_option('admin_email')) ?>
						<?php endif; ?>
					</p>
				</div>
				<?php
			}
		} else if ($spbas->check_for_updates(false, true)) {
			$latest_version = get_transient('mapsmarkerpro_latest');
			if ($latest_version === false) {
				$check_latest = wp_remote_get('https://www.mapsmarker.com/version.json', array(
					'sslverify' => true,
					'timeout' => 5
				));
				if (is_wp_error($check_latest) || $check_latest['response']['code'] != 200) {
					$latest_version = Maps_Marker_Pro::$version;
				} else {
					$latest_version = json_decode($check_latest['body']);
					if ($latest_version->version === null) {
						$latest_version = Maps_Marker_Pro::$version;
					} else {
						$latest_version = $latest_version->version;
					}
				}
				set_transient('mapsmarkerpro_latest', $latest_version, 60 * 60 * 24);
			}
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?= esc_html__('Warning: your access to updates and support for Maps Marker Pro has expired!', 'mmp') ?></strong><br />
					<?php if ($latest_version !== false && version_compare($latest_version, Maps_Marker_Pro::$version, '>')): ?>
						<?= esc_html__('Latest available version:', 'mmp') ?> <a href="https://www.mapsmarker.com/v<?= $latest_version ?>" target="_blank" title="<?= esc_attr__('Show release notes', 'mmp') ?>"><?= $latest_version ?></a> (<a href="https://www.mapsmarker.com/changelog/pro/" target="_blank"><?= esc_html__('show all available changelogs', 'mmp') ?></a>)<br />
					<?php endif; ?>
					<?= sprintf(esc_html__('You can continue using version %1$s without any limitations. However, you will not be able access the support system or get updates including bugfixes, new features and optimizations.', 'mmp'), Maps_Marker_Pro::$version) ?><br />
					<?= sprintf($l10n->kses__('<a href="%1$s">Please renew your access to updates and support to keep your plugin up-to-date and safe</a>.', 'mmp'), get_admin_url(null, 'admin.php?page=mapsmarkerpro_license')) ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Displays the changelog after an update
	 *
	 * @since 4.0
	 */
	public function changelog() {
		$changelog = get_option('mapsmarkerpro_changelog');

		if (!$changelog || strpos($this->page, 'mapsmarkerpro') === false) {
			return;
		}

		?>
		<style>
			#mmp-changelog-wrap {
				margin: 10px 20px 0 2px;
				padding: 5px;
				background-color: #ffffe0;
				border: 1px #e6db55 solid;
				border-radius: 5px;
			}
			#mmp-changelog-wrap h2 {
				margin: 0;
				padding: 0;
				font-weight: bold;
			}
			#mmp-changelog {
				overflow: auto;
				height: 205px;
				margin: 5px 0;
				border: thin dashed #e6db55;
			}
		</style>

		<div id="mmp-changelog-wrap">
			<h2><?= sprintf(esc_html__('Maps Marker Pro has been successfully updated from version %1s to %2s!', 'mmp'), $changelog, Maps_Marker_Pro::$version) ?></h2>
			<div id="mmp-changelog"><p><?= esc_html__('Loading changelog, please wait ...', 'mmp') ?></p></div>
			<button type="button" id="mmp-hide-changelog" class="button button-secondary"><?= esc_html__('Hide changelog', 'mmp') ?></button>
		</div>

		<script>
			jQuery(document).ready(function($) {
				var link = 'https://www.mapsmarker.com/?changelog=pro&from=<?= $changelog ?>&to=<?= Maps_Marker_Pro::$version ?>';

				$('#mmp-changelog').load(link, function(response, status, xhr) {
					if (status == 'error') {
						$('#mmp-changelog').append('<p><?= esc_html__('Changelog could not be loaded, please try again later.', 'mmp') ?></p>');
					}
				});

				$('#mmp-hide-changelog').click(function() {
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						context: this,
						data: {
							action: 'mmp_dismiss_changelog',
							nonce: '<?= wp_create_nonce('mmp-dismiss-changelog') ?>'
						}
					});

					$('#mmp-changelog-wrap').remove();
				});
			});
		</script>
		<?php
	}

	/**
	 * Dismisses the changelog
	 *
	 * @since 4.0
	 */
	public function dismiss_changelog() {
		if (!isset($_POST['nonce']) || wp_verify_nonce($_POST['nonce'], 'mmp-dismiss-changelog') === false) {
			return;
		}

		update_option('mapsmarkerpro_changelog', null);

		wp_die();
	}
}
