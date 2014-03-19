$(function() {

    $('<div class="closeButton">&times;<div>').appendTo('#feedback');
    $('.closeButton').click(function() {
        $(this).closest('#feedback').fadeOut();
    });

    $('.post_body img').wrap('<div class="image_cont">')
            .closest('div').append('<div class=image_pop_up>');

    $('.post_body img').each(function(n) {
        $(this).attr('data-title', $(this).attr('title')).attr('title', '');
    });


    $('.image_cont').hover(
            function() {
                $img = $(this).find('img');
                title = $img.attr('data-title');
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
        tag = $(event.target).text();
        filter = $(event.target).attr('data-filter');
        params = {
            'tag': tag,
            'filter': filter
        };
        $("<div>").addClass('tip').css({
            position: 'absolute',
            top: Math.ceil($(event.target).offset().top + $(event.target).height()) - 5,
            left: Math.ceil($(event.target).offset().left + $(event.target).width() / 2) - 40,
            display: 'none'
        }).append('<span class="tag_num">').prepend('<div class="transparent"><div class="triangle point"></div></div>').appendTo('body');
        $('.tag_num').wrap('<div class="black"></div>').load('/index/countTag',
                $.param(params),
                function() {
                    $(this).closest('.black').append(" записей с этим тэгом").closest('.tip').fadeIn('slow');
                });
    }, function() {
        $('.tip').remove();
        //$('.tip').fadeOut();
    });

    $('.remove_link').click(function() {
        return confirm('Вы точно хотите удалить этот ' + $(this).attr('data-type') + '?');
    });

    $('<img src="/css/images/glyphicons_050_link.png">').insertAfter($('a[href^="http"]')).addClass('icon').css({
        width: '12px',
        height: '12px',
        opacity: '0.5',
        'margin-left': '3px',
    });

    $('a.reply_link').click(function() {
        var toBeParent = $(this).closest('.comment').attr('id');
        $('#comment-form')
                .find("input[name='parentId']")
                .val(toBeParent).end()
                .insertAfter($(this)
                        .closest('.comment'));
        return false;
    });
    $('#add_comment').click(function() {
        var pos = $('#comment-form')
                .find("input[name='parentId']")
                .val(null)
                .end()
                .appendTo('#comments')
                .offset()
                .top;
        $('html, body').scrollTop(pos);
        return false;
    });

});



