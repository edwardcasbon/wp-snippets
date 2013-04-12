(function($){
	$(function(){
		$("ul#snippets a").on("click", function(){
			var $snippet = $(this);
			var $snippetText = '[snippet id="' + $snippet.data("snippet-id") + '" name="' + $snippet.html() + '"]';			
			window.send_to_editor($snippetText);
			
		});
	});
})(jQuery);