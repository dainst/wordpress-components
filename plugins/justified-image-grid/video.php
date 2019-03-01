<?php
	// Loaded as part of a template redirect, that's how it has access to WP functions
	if(empty($_GET["file"])){
		echo 'Video file is not specified.';
		die();
	}
	$jig_settings = get_option('jig_settings');

	// Defaults for these settings if they are not yet in the database
	if(empty($jig_settings['video_area_background'])){
		$jig_settings['video_area_background'] = 'transparent';
	}
	if(empty($jig_settings['video_autoplay'])){
		$jig_settings['video_autoplay'] = 'yes';
	}
	$videoplayer_pos = strpos($_GET["file"], '|videoplayer');
	if($videoplayer_pos !== false){
		// Link came from prettyphoto and the whole file index contains an urlencoded value
		$file = substr($_GET["file"], 0, $videoplayer_pos);
		$file_parts = explode('|poster=', $file);
		$file = esc_attr($file_parts[0]);
		$poster = esc_attr(urldecode($file_parts[1]));

	} else {
		// Link came from another lightbox
		$file = esc_attr($_GET["file"]);
		$poster = !empty($_GET["poster"]) ? esc_attr($_GET['poster']) : false;
	}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
	<title><?php _e('Justified Image Grid video player', 'jig_td'); ?></title>
	<link rel='stylesheet' id='mediaelement-css'  href='<?php echo includes_url("js/mediaelement/mediaelementplayer.min.css"); ?>' type='text/css' media='all' />
	<link rel='stylesheet' id='wp-mediaelement-css'  href='<?php echo includes_url("js/mediaelement/wp-mediaelement.css"); ?>' type='text/css' media='all' />
	<script type='text/javascript'>
		var _wpmejsSettings = {"pluginPath":"<?php echo str_replace('/', '\/', includes_url('js/mediaelement/', 'relative')); ?>"};
		var _JIGvideo = {
			"autoplay": <?php echo $jig_settings['video_autoplay'] == 'yes' ? 'true' : 'false'; ?>,
			"site_url": "<?php echo site_url(); ?>",
			"isset_referrer": <?php echo (isset($_SERVER["HTTP_REFERER"]) ? 'true' : 'false'); ?>,
			"ref": <?php echo isset($_SERVER["HTTP_REFERER"]) ? '"'.esc_attr($_SERVER['HTTP_REFERER']).'"' : 'false'; ?>,
			"hosted_here": <?php echo '"'.$file.'".indexOf("'.site_url().'") != -1'.(!empty($poster) ? ' && "'.$poster.'".indexOf("'.site_url().'") != -1' : ''); ?>,
			"inIframe": function () {
				try {
					return window.self !== window.top;
				} catch (e) {
					return true;
				}
			},
			"canPlay": function (){
				var player = new MediaElementPlayer('jigVideoPlayer');
				if (_JIGvideo.autoplay === true) {
					player.play();
				}
			}
		};
	
		/**
		 * Forced sequential blocking JS loading
		 * Caching plugins were conflicting bad with this delicate order
		 */
		(function(d, s) {
		    var head = document.getElementsByTagName("head")[0];

			var jq = d.createElement(s); 
			jq.type = "text/javascript";
			head.appendChild(jq);
			jq.onload  = function() {
				var meap = d.createElement(s); 
				meap.type = "text/javascript";
				head.appendChild(meap);
				meap.onload  = function() {
					var wpme = d.createElement(s); 
					wpme.type = "text/javascript";
					head.appendChild(wpme);
					wpme.onload  = function() {
						var jigv = d.createElement(s); 
						jigv.type = "text/javascript";
						jigv.src = '<?php echo plugins_url('js/jig-video.js', __FILE__); ?>';
						head.appendChild(jigv);
					}
					wpme.src = '<?php echo includes_url("js/mediaelement/wp-mediaelement.js"); ?>';
				}
				meap.src = '<?php echo includes_url("js/mediaelement/mediaelement-and-player.min.js"); ?>';
			}
			jq.src = '<?php echo includes_url("js/jquery/jquery.js"); ?>';
		}(document, 'script'));
	</script>



	<style type="text/css">
		html, body, div, span, applet, object, iframe, img, embed, audio, video {
			margin: 0;
			padding: 0;
			border: 0;
			font-size: 100%;
			font: inherit;
			vertical-align: baseline;
		}
		body {
			line-height: 1;
		}
		html, body{
			overflow: hidden;
			width: 100%;
			height: 100%;
		}
		video{
			max-width: 100%;
			max-height: 100%;
		}
		.mejs-container, .mejs-embed, .mejs-embed body{
			background: <?php echo $jig_settings['video_area_background']; ?>; /* player background */
		}
		.mejs-controls .mejs-button button:focus{
			outline: none;
		}
		.mejs-controls .mejs-time-rail .mejs-time-loaded {
			background: #676767;
		}
		/*.mejs-overlay-loading{
			display: none;
		}*/
		.me-cannotplay{
			height: auto !important;
			max-height: 100%;
		}
		.securityNotice {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			font-size: 16px;
			text-align: center;
		    font: small arial,sans-serif;
		   	color:#b71c1c;
		   	line-height: 1.8;
		}
	</style>
</head>

<body>
	<video id="jigVideoPlayer" height="100%" width="100%"  <?php echo !empty($poster) ? 'poster="'.$poster.'"' : ''; ?> src="<?php echo $file; ?>">
	</video>
</body>
</html>