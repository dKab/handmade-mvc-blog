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
    
    $('.wide').click(function() {
        $('.reference-block').slideToggle();
        $(this).find('.triangle.small').toggleClass('closed');
    });
    
  
    $('a.tag').hover(function(event) {
        //console.log(event.target);
        //left = $(event.target).offset().left + $(event.target).outerWidth()/2;
        //top = $(event.target).offset().top + $(event.target).outerHeight();
        tag = $(event.target).text();
        controller = $(event.target).attr('data-controller');
        params = {
            'tag': tag,
        };
        $("<div>").addClass('tip').css({
            position: 'absolute',
            top: Math.ceil($(event.target).offset().top + $(event.target).height())-5,
            left: Math.ceil($(event.target).offset().left + $(event.target).width()/2)-40,
            display: 'none'
        }).append('<span class="tag_num">').prepend('<div class="transparent"><div class="triangle point"></div></div>').appendTo('body');
        $('.tag_num').wrap('<div class="black"></div>').load('/'+ controller + '/countTag', 
                $.param(params),
         function() {
            $(this).closest('.black').append(" записей с этим тэгом").closest('.tip').fadeIn('slow'); 
         });
    }, function() {
        $('.tip').remove();
        //$('.tip').fadeOut();
        });
    
    
    
    
});



