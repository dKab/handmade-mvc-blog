     $(function() {
            var $new = $('input[name="new_category"]');
            var $existing = $('select[name="categories"]');
            var $radioButtons = $('input[name="category_type"]');
     
            if ( $radioButtons.eq(1).is(':checked') )  {
                $existing.hide();
            } else if ($radioButtons.eq(0).is(':checked') ) {
                $new.hide();
            }
            $radioButtons.on('change', function() {
               if ($(this).val() === "new_category") {
                       $existing.val('').hide();
                       $new.show();
               } else if ($(this).val() === 'categories') {
                   $existing.show();
                   $new.val(null).hide();
               }
           });
        }); 

