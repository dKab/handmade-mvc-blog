        $(function() {
            var $new = $('input[name="new_category"]');
            var $existing = $('select[name="categories"]');
            var $radio = $('input[name="category_type"]');
            
            if ($radio && $radio.val() === 'categories') {
                $new.hide();
            }
            
            $radio.on('change', function(){
               if ($(this).val() === "new_category") {
                       $existing.val('').hide();
                       $new.show();
               } else if ($(this).val() === 'categories') {
                   $existing.show();
                   $new.val(null).hide();
               }
           });
        }); 


