(function($, _JIGvideo){
	$(document).ready(function(){
		//in iframe, only if same host
		try{
			if(_JIGvideo.inIframe() === true && _JIGvideo.site_url.indexOf(window.top.location.host) != -1){
				_JIGvideo.canPlay();
				return;
			}
		} catch (e){}
		
		// if referrer is the same site
		if(_JIGvideo.isset_referrer){
			var hostMatchPattern = new RegExp('^https?:\/\/'+window.location.host+'/', 'i');
			if(hostMatchPattern.test(_JIGvideo.ref) != -1){
				_JIGvideo.canPlay();
				return;
			}
		}

		// content is hosted on the site
		if(_JIGvideo.hosted_here){
			_JIGvideo.canPlay();
			return;
		}

		/*	in case it's in a lightbox(iframe) but called from another site or it's not in a lightbox(iframe)
			and has no referrer or it's not the main site
			and the content is not hosted on the main site
		*/
		
		$('video').hide().each(function () {
			this.pause();
			$(this).attr('data-src',$(this).attr('src')).attr('src','');
		});
		$('body').append('<div class="securityNotice"><strong>Security notice</strong><br />Video player is only accessible from a lightbox and on the same host.<br />Otherwise it has to have the parent site as the referrer or the video and its poster hosted on the same site.</div>');
	});
})(jQuery, _JIGvideo);