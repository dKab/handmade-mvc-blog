$(function() {
    
$('<div class="closeButton">&times;<div>').appendTo('#feedback');
$('.closeButton').click(function() {
    $(this).closest('#feedback').fadeOut(); 
    });
    
    $('#posts img').wrap('<div class="image_cont">')
            .closest('div').append('<div class=image_pop_up>');
    $('.image_cont').hover(
            function() {
                title = $(this).find('img').attr('title');
                if (title) {
                    $(this).find('.image_pop_up').text(title).animate(
                        {
                            "bottom": 0
                        }, 300);
                }
            }, function() {
                $(this).find('.image_pop_up').animate(
                        {
                            "bottom": "-40px"
                        }, 300);
            }
            );
});


