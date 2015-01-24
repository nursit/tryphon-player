var tryphon_player_script;
var tryphon_loader;
(function(){
	function init_players(){
		jQuery('a.spip_out').filter('[href*="audiobank.tryphon.eu"]').addClass('tryphon-player');
		if (jQuery("a.tryphon-player").length){
			if (typeof tryphon_loader == "undefined"){
				tryphon_loader = jQuery.getScript(tryphon_player_script,init_players);
				return;
			}
			if (typeof Tryphon == "undefined") return;
			Tryphon.Player.setup({
        "url_rewriter": function(url) {
          return "tryphon.api/token/?u=" + encodeURIComponent(url);
        },
				"ignore_player_css_url": true
      });
			Tryphon.Player.load();
		}
	}
	jQuery(init_players);
	onAjaxLoad(init_players);
})();