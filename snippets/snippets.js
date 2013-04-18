(function($){
	$(function(){
		// Default plugin settings.
		var settings = {
			type: 'token'
		}
		
		// Get the settings from WordPress.
		$.ajax({
			type: 'GET',
			url: '/wp-admin/admin-ajax.php',
			data: { action: 'getSnippetsSettings' },
			dataType: 'json',
			success: function(data) { settings = data; }, 
			error: function(MLHttpRequest, textStatus, errorThrown) { console.log(errorThrown); }
		});
		
		// Add a snippet.
		$("ul#snippets a").on("click", function(event){
			event.preventDefault();
			var $snippet = $(this);
			var $snippetText = "";
						
			if(settings.type == "token") {
				var $snippetText = '[snippet id="' + $snippet.data("snippet-id") + '" name="' + $snippet.html() + '"]';
				window.send_to_editor($snippetText);
				
			} else if(settings.type == "full") {
				$.ajax({
					type: 'GET',
					url: '/wp-admin/admin-ajax.php',
					data: { action: 'getSnippet', snippetId: $snippet.data("snippet-id") },
					success: function(snippet) { window.send_to_editor(snippet); }, 
					error: function(MLHttpRequest, textStatus, errorThrown) { console.log(errorThrown); }
				});	
			}
		});
	});
})(jQuery);