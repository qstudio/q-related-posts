(function($){
	simpleRelatedPosts = 
	{
		init:function()
		{
			this.keyup();
			this.sortable();
			this.remove();
			this.submit();
			this.reset();
		},
		
		sortable: function()
		{
			$( '.qrp_relationship .relationship_right .relationship_list' ).sortable();
		},
		
		keyup:function()
		{
			$('.qrp_relationship .relationship_left .relationship_search').keyup(function(e){
				var param = {
					action: 'qrp_search_posts',
					s: $(this).val()
				}
				$.post(ajaxurl, param, function(ret) {
					if ( ret == '' ) {
						return false;
					}

					var output = '';
					$.each(ret, function(index, item) {
						var title = '';
						
						if ( item.post_thumbnail != '' ) {
							title += '<div class="result-thumbnail">'+item.post_thumbnail+'</div>';
						}
						title += item.post_title;
						
						output += '<li><a class="" data-post_id="'+item.ID+'" href="'+item.permalink+'"><span class="title">'+title+'</span><span class="sirp-button"></span></a></li>' + "\n";
					});
					$('.qrp_relationship .relationship_left .relationship_list').html(output);
					simpleRelatedPosts.add();
					$('.qrp_relationship .relationship_right .relationship_list li').each(function(index, item) {
      					$('.qrp_relationship .relationship_left .relationship_list li').each(function(index_2, item_2) {
      						if($(item).children('a').attr('data-post_id') == $(item_2).children('a').attr('data-post_id')) {
	      						$(item_2).addClass('hide');
		  					}
		  				});
		  			});
				},'json');
			});
		},
		
		add: function()
		{
			$('.qrp_relationship .relationship_left .relationship_list li a').on( 'click', function(e){
                            e.preventDefault();

                            var post_id = $(this).attr('data-post_id'), flg = true;

                            $('.qrp_relationship .relationship_right .relationship_list li').each(function(index, item) {
      				if ( ( index+1 ) >= objectL10n.max_posts ) {
                                    alert( objectL10n.alert.replace( '%d', objectL10n.max_posts ) );
                                    flg = false;
      				}
      			
      				if($(item).children('a').attr('data-post_id') == post_id) {
                                    flg = false;
                                }
                            });
	  			
                            if (flg) {
                                $(this).closest('li').clone(false).prependTo('.qrp_relationship .relationship_right .relationship_list').css('background-color', '#EAF2FA').animate({
                                    backgroundColor: "#FFFFF"
                                }, 1200);
                                $(this).closest('li').addClass('hide');
                                simpleRelatedPosts.remove();
      			}
			});
		},
		
		remove: function()
		{

			$('.qrp_relationship .relationship_right .relationship_list li a .sirp-button').on( 'click', function(e){
			 	e.preventDefault();
			 	$(this).closest('li').fadeOut("slow").queue(function () {
					$(this).remove();
      			});
      			
      			var post_id = $(this).closest('a').attr('data-post_id');
      			$('.qrp_relationship .relationship_left .relationship_list li').each(function(index, item) {
      				if($(item).children('a').attr('data-post_id') == post_id) {
	      				$(item).removeClass('hide');
      				}
      			});
      			
			});
		},
		
		submit: function()
		{
			$('#post').submit(function(){
				$('.qrp_relationship .relationship_right .relationship_list li').each(function(index, item) {
					$('<input />').attr('type', 'hidden')
					.attr('name', 'q_related_posts[]')
					.attr('value', $(item).children('a').attr('data-post_id'))
					.appendTo('#post');
				});
			});
		},
		
		reset: function()
		{
			$('.qrp_relationship .relationship_right #sirp-reset').on('click', function(e){
				var param = {
                                    action: 'qrp_reset_related_posts',
                                    post_id: objectL10n.post_id 
				}
				$.post( ajaxurl, param, function( ret ) {
					if ( ret == '' ) {
                                            //console.log("nada..");
                                            return false;
					}

					var output = '';
					$.each(ret, function(index, item) {
                                            var title = '';

                                            if ( item.post_thumbnail != '' ) {
                                                title += '<div class="result-thumbnail">'+item.post_thumbnail+'</div>';
                                            }
                                            title += item.post_title;

                                            output += '<li><a class="" data-post_id="'+item.ID+'" href="'+item.permalink+'"><span class="title">'+title+'</span><span class="sirp-button"></span></a></li>' + "\n";
					});
					$('.qrp_relationship .relationship_right .relationship_list').html(output);
					simpleRelatedPosts.remove();
					$('.qrp_relationship .relationship_right .relationship_list').css('background-color', '#EAF2FA').animate({
                                            backgroundColor: "#FFFFF"
					}, 1200);
				},'json');
			});
		}
		
	},
	$(document).ready(function ()	
    {
        simpleRelatedPosts.init();
    })
})(jQuery);