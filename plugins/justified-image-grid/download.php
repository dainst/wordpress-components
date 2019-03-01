<?php
// Photo downloader script for Justified Image Grid
// Determines remote file size by just downloading the headers and transfers the remote image to the viewer

// Only allow images and from an absolute path
if(!empty($_GET['file'])){
	if (preg_match('#^https?(?:://|%3A%2F%2F)[^?]+\.(jpe?g|gif|bmp|png|webp)($|\?.+)#i', $_GET['file'])) {
		$file_url = str_replace(' ', '%20', $_GET['file']); // handle spaces too
	}else{
		header('HTTP/1.0 403 Forbidden');
		exit;
	}
}else{
	header ("HTTP/1.1 404 Not Found");
	exit;
}
function retrieve_remote_file_size($url, $fallback = false){
	if(function_exists('curl_version') && $fallback === false){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_NOBODY, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:50.0) Gecko/20100101 Firefox/50.0");
		if(!ini_get("safe_mode") && !ini_get("open_basedir")){
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		}else{
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		}
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // local testing only - SSL certificate problem: unable to get local issuer certificate
		$data = curl_exec($ch);
		if($data === false){
			// Any CURL error: fallback to regular way
			return retrieve_remote_file_size($url, true);
		}
		$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		curl_close($ch);
		return $size;
	}elseif(function_exists('get_headers')){
		$remote_header = get_headers($url, true);
		if($remote_header !== false){
			$remote_header = array_change_key_case($remote_header);
			if(!empty($remote_header['content-length'])){
				$filesize = (float) $remote_header['content-length'];
			}
		}
		return isset($filesize) ? $filesize : false;
	}else{
		return false;
	}
}

function get_data($url, $force_curl = false) {

	if($force_curl === true || (!ini_get('allow_url_fopen') && function_exists('curl_version'))){ // CURL is slower in this case but it works if ini_get('allow_url_fopen') is false
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:50.0) Gecko/20100101 Firefox/50.0");
		if(!ini_get("safe_mode") && !ini_get("open_basedir")){
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		}else{
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		}
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // local testing only - SSL certificate problem: unable to get local issuer certificate
		$data = curl_exec($ch);
		if($data === false){
			error_log("JIG Download image script CURL error - ".curl_error($ch));
		}else{
			echo $data;
		}
		curl_close($ch);
	}else{
		ob_start();
		@readfile($url);
		$read_result = ob_get_contents();
		ob_end_clean();
		if(empty($read_result)){
			get_data($url, true);
		}else{
			echo $read_result;
		}
	}
}

$filesize = retrieve_remote_file_size($file_url);
if($filesize != -1){
	if($filesize === false){
		header('Location: '.$file_url);
		exit;
	}elseif($filesize > 0 && $filesize < 52428800){
		$file_name = basename($file_url);
		if(strpos($file_name, '?') !== false){
			$file_name = substr($file_name, 0, strpos($file_name, '?'));
		}
		if(ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off');
		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false); // required for certain browsers
		header('Content-Type: application/octet-stream'); // delibaretely octet-stream and not force-download 
		header("Content-Transfer-Encoding: Binary"); 
		header("Content-disposition: attachment; filename=\"".$file_name."\""); // Quotes are required here
		header("Content-Length: ". (string) $filesize);
		ob_clean();
		flush();
		get_data($file_url);
	}elseif($filesize == 0){
		// Could not determine the remote image size, just display it via the browser, nothing else that can be done really
		header('Location: '.$file_url);
	}else{
		echo 'The requested file is too large.';
	}
}else{
	// if image is 404 it'll show as 404 anyway, if it's there just not pullable via download.php then it'll display the image
	header('Location: '.$file_url);
}
exit;