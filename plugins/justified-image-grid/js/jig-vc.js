(function($){

window['JIG_VC'] = {};

// Backend
// Comes from vc_map -> 'js_view' => 'VcJustifiedImageGrid'
window.VcJustifiedImageGrid = vc.shortcode_view.extend({
	// Render method called after element is added( cloned ), and on first initialisation
	render: function () {
		JIG_VC.obj = this;
		// If VC "Add Element" dialog is open upon rendering the JIG element, then it's newly added
		if(vc.add_element_block_view.$el.hasClass('vc_active')){
			jig_vc_sce(this.model.get('params'));
		}
		window.VcJustifiedImageGrid.__super__.render.call(this); //make sure to call __super__. To execute logic fron inherited view. That way you can extend original logic. Otherwise, you will fully rewrite what VC will do at this event
		return this;
	},
	editElement: function (e) {
		JIG_VC.obj = this;
		jig_vc_sce(this.model.get('params'));
		return false;
	}
});

// Frontend
if(typeof(window.InlineShortcodeView) !== 'undefined'){
	var should_open_sce = false;
	jQuery(window).on('vc_build',function(){
		vc.add_element_block_view.$el.find('[data-element="jig_vc"]').on('click',function(){
			should_open_sce = true;
		});
	});
	// This name is defined automatically (InlineShortcodeView_you, for Frontend editor only
	window.InlineShortcodeView_jig_vc = window.InlineShortcodeView.extend({
		// Render called every time when some of attributes changed.
		render: function () {
			window.InlineShortcodeView_jig_vc.__super__.render.call(this); // it is recommended to call parent method to avoid new versions problems.
			if(should_open_sce){
				JIG_VC.obj = this;
				jig_vc_sce(this.model.get('params'));
			}
			return this;
		},
		edit: function( e ) {
			JIG_VC.obj = this;
			jig_vc_sce(this.model.get('params'));
			return false;
		},
		update: function(model) {
			should_open_sce = false;
			window.InlineShortcodeView_jig_vc.__super__.update.call(this,model);
			return this;
		}
	});
}

var $sce,
	sce_url = ajaxurl + (ajaxurl.indexOf('?') == -1 ? '?' : '&') + 'action=jig_shortcode_editor',
	$iframe = $('<iframe frameborder="0" marginwidth="0" marginheight="0"></iframe>');
	$iframe.attr({ src: sce_url });

function jig_vc_sce(params){
	if(typeof params.content == 'undefined'){
		return;
	}

	if(typeof $sce == 'object' && $sce.dialog("isOpen")){
		var temp_JIG_VC_obj = JIG_VC.obj; // The newly edited JIG instance object would get lost upon closing
		JIG_VC.hotSwap = true;
		$sce.dialog('close');
		JIG_VC.obj = temp_JIG_VC_obj; // Puttting it back like nothing's changed

	}
	
	if(typeof(window.InlineShortcodeView) !== 'undefined'){
		JIG_VC.frontend = true;
	}else{
		JIG_VC.frontend = false;
	}

	JIG_VC.params = params;
	JIG_VC.shortcodeSnapshot = params.content; // Save the original shortcode upon opening the SCE

	if(typeof $sce !== 'object'){
		
		$sce = $('<div id="jig-sce-jquery-ui-dialog"></div>').append($iframe).appendTo("body").dialog({
			id       : 'jig-sce-jquery-ui-dialog',
			width    : 900,
			height   : 550,
			resizable: false,
			open: function () {

				// This resets certain aspects of the SCE frame, that need to be reset from the inside
				if(typeof $iframe[0].contentWindow.editShortcodeFromVC !== 'undefined'){
					$iframe[0].contentWindow.editShortcodeFromVC();
					$iframe[0].contentWindow.scrollTo(0, 0);
				}

				// This makes sure the SCE is not dragged out from the window frame
				if(JIG_VC.frontend){ // (causes bug on backend!)
					var $uiDialog = $('.ui-dialog[aria-describedby="jig-sce-jquery-ui-dialog"]');
					if($uiDialog.offset().top + $uiDialog.height() > $(window).height()){
						$uiDialog.css({'top':'auto','bottom':'20px','height':'auto'});
					}
				}

				// Change controls in SCE according to whether or not the calling environment is VC or regular
				if(typeof $iframe[0].contentWindow.detectVC !== 'undefined'){
					$iframe[0].contentWindow.detectVC();
				}

				// Storing the obj data in another variable that doesn't get overwritten until the next open
				// This allows hot swapping later
				JIG_VC.prevObj = JIG_VC.obj;

			},
			close: function () {
				if(typeof $iframe[0].contentWindow.resetSCE !== 'undefined'){
					$iframe[0].contentWindow.resetSCE();
				}
				if(typeof JIG_VC.shortcode == 'undefined'){
					// This helps revert the previews's changes to the original
					// Will not do much (will not re-create JIGs) if nothing changed!
					if(typeof JIG_VC.hotSwap == 'undefined'){
						JIG_VC.obj.model.save('params', {'content': JIG_VC.shortcodeSnapshot});
					}else{
						// Hot swapping means replacing the SCE with data from another JIG, without manually closing it first
						JIG_VC.prevObj.model.save('params', {'content': JIG_VC.shortcodeSnapshot});
					}
				}

				should_open_sce = false;

				$('.ui-dialog[aria-describedby="jig-sce-jquery-ui-dialog"]')
					.removeClass('previewingShortcode')
					.find('.ui-dialog-titlebar-collapsed').click(); // If it's collapsed, expand it
					JIG_VC = {};

				return;
			}
		});
		if(JIG_VC.frontend){
			var $uiDialog = $('.ui-dialog[aria-describedby="jig-sce-jquery-ui-dialog"]'),
				originalHeight = $uiDialog.height(),
				$titlebar = $uiDialog.find('.ui-dialog-titlebar'),
				titleBarCollapsed = false,
				$closeButton = $('.ui-dialog[aria-describedby="jig-sce-jquery-ui-dialog"] .ui-dialog-titlebar-close'),
				$minimizeButton = $closeButton.clone().insertBefore($closeButton).attr('class','ui-dialog-titlebar-minimize');

			$.extend( $.easing, {
				// opacity changes and fades are fine with swing
				JIGswing: function(p) {
					return 0.5 - Math.cos(p*Math.PI)/2;
				},
				// for 2D movements
				JIGeaseInOutQuad: function(x) {
					return x < 0.5 ? 2*x*x : 1-Math.pow(-2*x+2, 2)/2;
				}
			});
			// Ability to collapse or expand the SCE to allow testing the grid during livepreview
			$minimizeButton.on('click',function(){
				var $this = $(this);
					
				if(!titleBarCollapsed && !$sce.is(':animated')){
					$uiDialog.css('height','auto');
					$sce.data('originalHeight',$sce.height());
					$titlebar.addClass('ui-dialog-titlebar-collapsed');
					$sce.animate({height:0, opacity:0}, 300, "JIGeaseInOutQuad", function(){
						titleBarCollapsed = true;
					});
				}else if(!$sce.is(':animated')){
					var windowHeight = $(window).height();

					// Allow animation from the bottom up if the collapsed bar is at the bottom of the window
					if($uiDialog.offset().top + originalHeight > windowHeight){
						$uiDialog.css('bottom',function(){
							return windowHeight-$uiDialog.offset().top-$uiDialog.outerHeight();
						}).css({'top':'auto','height':'auto'});
					}
					
					$sce.animate({
									height: $sce.data('originalHeight'),
									opacity: 1
								}, 300, "JIGeaseInOutQuad", function(){
									$uiDialog.height(originalHeight);
									$titlebar.removeClass('ui-dialog-titlebar-collapsed');
									titleBarCollapsed = false;
								});
				}
			});
		}
	}else{
		$sce.dialog('open');
	}
}

// Closing of the dialog
$('body').on('click', '.ui-dialog[aria-describedby="jig-sce-jquery-ui-dialog"]', function(e){
	if($(e.target).is('.ui-dialog')){
		$sce.dialog('close');
	}
});

})(jQuery);