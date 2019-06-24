<?php
		$_error = NULL;
		if (array_key_exists('error', $_REQUEST)) {
			$_error = $_REQUEST['error'];
		}
?>
		<section>
			<div class="header">
				<a href="#">
					<img src="<?php echo plugins_url($this->getPluginLogo(), __FILE__); ?>" />
				</a>
				<span>
					<img src="<?php echo plugins_url('../img/hdr-logo-bv.png', __FILE__); ?>" />
				</span>
			</div>
			<div class="lft-fold">
				<div class="lft-fold-txt">
					<h2>Migrate My Site to Cloudways </h2>
					<p>
						This plugin makes it easy for anyone to migrate their WordPress websites from any hosting provider to Cloudways for free.
					</p>
				</div>
				<form id="cloudways_migrate_form" dummy=">" action="<?php echo $this->bvmain->appUrl(); ?>/home/migrate" onsubmit="document.getElementById('migratesubmit').disabled = true;" style="padding:0 2% 2em 1%;" method="post" name="signup" class="pull-wide">
					<?php if ($_error == "email") {
						echo '<div class="error" style="padding-bottom:0.5%;"><p>There is already an account with this email.</p></div>';
					} else if ($_error == "blog") {
						echo '<div class="error" style="padding-bottom:0.5%;"><p>Could not create an account. Please contact <a href="http://blogvault.net/contact/">blogVault Support</a><br />
							    <font color="red">NOTE: We do not support automated migration of locally hosted sites.</font></p></div>';
					} else if (($_error == "custom") && isset($_REQUEST['bvnonce']) && wp_verify_nonce($_REQUEST['bvnonce'], "bvnonce")) {
						echo '<div class="error" style="padding-bottom:0.5%;"><p>'.base64_decode($_REQUEST['message']).'</p></div>';
					}
					?>
					<?php echo $this->siteInfoTags(); ?>
					<input type="hidden" name="bvsrc" value="wpplugin" />
					<input type="hidden" name="migrate" value="cloudways" />
					<input type="hidden" name="type" value="sftp" />
					<div class="form-col-6">
						<div>
							<label>Email </label>
							<input type="email" placeholder="me@domain.com" id="email" name="email">
						</div>
						<div>
							<label>Database Name </label>
							<input type="text" placeholder="See Cloudways Platform" name="appfolder">
						</div>
						<div>
							<label>SFTP Username </label>
							<input type="text" placeholder="See Cloudways Platform" name="username">
						</div>
					</div>
					<div class="form-col-6">
						<div>
							<label>Destination Site URL </label>
							<input type="text" placeholder="https://example.cloudwaysapps.com" name="newurl" id="newurl">
						</div>
						<div>
							<label>Server Public IP Address </label>
							<input type="text" placeholder="ex. 12.34.567.890" name="address" id="address">
						</div>
						<div>
							<label>SFTP Password </label>
							<input type="password" placeholder="See Cloudways Platform" name="passwd" id="passwd">
						</div>
					</div>
					<div class="footer pull-wide">
						<br><input type="checkbox" onchange="document.getElementById('migratesubmit').disabled = !this.checked;" name="consent" value="1"/>I agree to Blogvault <a href="https://blogvault.net/tos" target="_blank" rel="noopener noreferrer">Terms of Service</a> and <a href="https://blogvault.net/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy</a><br>
						<input type='submit' disabled id='migratesubmit' value='Migrate' style="width: 15%;margin-top: 1rem;">
					</div>
				</form>
			</div>
			<div class="rt-fold">
				<div class="rt-fold-txt">
					<h2>Need Help?</h2>
				</div>
				<div class="rt-fold-card">
					<div class="card-hd">
						<img src="<?php echo plugins_url('../img/rt-fold-card.png', __FILE__); ?>"/>
					</div>
					<p>For step by step instructions, read the supporting  <a href="https://support.cloudways.com/how-to-migrate-wordpress-to-cloudways/?utm_source=wp-migrator&utm_campaign=migrator-plugin">Knowledge Base</a>.</p>
				</div>
			</div>
		</section>