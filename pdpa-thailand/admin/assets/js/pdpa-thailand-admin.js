(function($){
    $(document).ready(function(){   
                     
        // Pdpa List
        // $('.pdpa--list').sortable();    

        // Dynamic edit page
        $('[name="pdpa_thailand_msg[policy_page]"]').change(function(){
            var policy_page_id = $(this).val();
            var policy_edit = $('.policy--page-edit');
            var policy_edit_url = pdpa_thailand.policy_edit_url.split('&');            
            policy_edit.attr('href', policy_edit_url[0] + policy_page_id + '&' + policy_edit_url[1]);
        });

        $('.pdpa--add-cookie, #dpdpa--upload, .dpdpa--logo-delete').click(function(e){
            e.preventDefault();
            return false;
        });
        
        // Refresh cookie
        $('.refresh--cookie').click(function(){

            var ele = $(this).prev();
            ele.addClass('loading');
        
            var data = {
                'action': 'reset_cookie_id',
                'nonce' : pdpa_thailand.nonce,
            };

            $.post(
                ajaxurl, 
                data, 
                function(data) {
                    // console.log(data);
                    ele.removeClass('loading');
                    $('[name="pdpa_thailand_settings[cookie_unique_id]"]').val(data);
            });
            return false;
        });

        // Rating
        $('.dpdpa--rating').click(function(){
            
            var data = {
                'action': 'rating_saved',
                'nonce' : pdpa_thailand.nonce,
                'status' : $(this).attr('attr-status'),
            };

            $.post(
                ajaxurl, 
                data, 
                function(data) {
                    $('.dpdpa--info').hide();

                    if ( data == 'yes' ) {
                        // Open new tab
                        window.open('https://wordpress.org/support/plugin/pdpa-thailand/reviews/?filter=5/#new-post', '_blank');
                    }
            });
            return false;
        });

        var pdpa_template = $('.pdpa--li_template').html(); 
        
        $('.pdpa--reset-cookie').click(function(){
            
            $('.pdpa--list').html(pdpa_template);
            $('.pdpa--list li').last().find('[name="consent_title[]"]').select();
            return false;
        });
        
        $(document).on('click', '.pdpa--list .accordion', function(){
            var li = $(this).parents('li');
            li.toggleClass('active');
            return false;
        });

        // Delete cookie
        $(document).on('click', '.pdpa--delete-button', function(){
            if ( confirm(pdpa_thailand.delete_layer) ) {
                $(this).parents('li').remove();
            }
            return false;
        });

        // $(document).on('keypress', 'input', function(e){
        //     if (e.which == 13) {                
        //         // $('form').submit();
        //     }
        // });
          
        // Keyup check cookie name
        $(document).on('keyup', '.pdpa--list [name="cookie_name[]"]', function(event){

            // Clear error
            $(this).removeClass('error');
            $(this).next().text('');
            // Clear space
            $(this).val($(this).val().trim());
            
            // Check character
            var english = /^[ A-Za-z_0-9-_]*$/i;
            if ( !english.test($(this).val()) ) {          
                $(this).addClass('error');
                $(this).next().text(pdpa_thailand.error_cookie_name);
                // $(this).val($(this).val().slice(0,-1));
            }                    

            // Check unique 
            UniqueCookieName(false);

        });

        // Submit click
        if ( $('body.toplevel_page_pdpa-thailand').length > 0 ) {
            
            var result = 0;            

            $('#submit').click(function(e){
                result = refreshCookies();                
                // console.log($('.regular-text.error').first().offset().top);

                // Check unique 
                UniqueCookieName(true);

                if ( result > 0 ) {                    
                    if ( $('.regular-text.error').length > 0 ) {
                        var scrollTo = $('.regular-text.error').first().offset().top;
                        $('html, body').animate({scrollTop : scrollTo - 100}, 300);
                    }                    
                    e.preventDefault();
                }

            });
        } 

        // Max popup width
        $(document).on('change keyup', '[name="appearance_container_point"],[name="appearance_container_size"]', function(){
            var size = $('[name="appearance_container_size"]').val();
            var point = $('[name="appearance_container_point"]').val();
            
            var size_point = size + '|' + point;
            $('[name="pdpa_thailand_appearance[appearance_container_size]"]').val(size_point);
        });


        // ----- Function ----- //
        function checkDeleteButton() {
            if ( $('.dpdpa--logo-box img').attr('src') == '' ) {
                $('.dpdpa--logo-delete').hide();
            } else {
                $('.dpdpa--logo-delete').show();
            }
        }
        
        // Refresh cookies
        function refreshCookies() {
            
            var result = 0;
            
            // Check in every field
            $('.pdpa--list li').each(function(){

                var chk = 0;
                var cookie_name = $(this).find('[name="cookie_name[]"]');
                var consent_title = $(this).find('[name="consent_title[]"]');
                var consent_description = $(this).find('[name="consent_description[]"]');

                // Validate Empty                
                chk += validateEmpty(cookie_name);
                chk += validateEmpty(consent_title);
                chk += validateEmpty(consent_description);                
                
                // Check character
                var english = /^[ A-Za-z_0-9-_]*$/i;
                if ( !english.test(cookie_name.val()) ) {
                    chk = 1;                                        
                    cookie_name.next().text(pdpa_thailand.error_cookie_name);
                    // $(this).val($(this).val().slice(0,-1));                    
                }
       
                if ( chk >= 1 ) {
                    consent_title.focus();                    
                    cookie_name.addClass('error');
                    cookie_name.parents('li').addClass('active');
                } else {
                    cookie_name.removeClass('error');                    
                }

                result = result + chk;
                
            });

            return result;
        }

        // Check cookie unique
        function UniqueCookieName(scroll) {
            var cookie_names = [];
            $('.pdpa--list [name="cookie_name[]"]').each(function(){
                cookie_names.push($(this).val());
            });

            var result = hasDuplicates(cookie_names);            
            if ( result ) {                
                var last_cookie_name = $('.pdpa--list li').last().find('[name="cookie_name[]"]');
                last_cookie_name.addClass('error');
                // Open popup
                last_cookie_name.parents('li').addClass('active');
                // Print error
                last_cookie_name.next().text(pdpa_thailand.error_cookie_unique);

                if ( scroll ) {
                    // Scroll to that element
                    var scrollTo = last_cookie_name.first().offset().top;
                    $('html, body').animate({scrollTop : scrollTo - 100}, 300); 
                }
                event.preventDefault();
                return false;
            }                                
        }

        function validateEmpty(ele) {
              
            // Clear error
            ele.removeClass('error');
            ele.next().text('');
            // Clear space
            ele.val(ele.val().trim());

            if ( ele.val() == '' ) {
                ele.addClass('error');
                return 1;
            } else
                return 0;
        }

        // Check array dupplicate
        function hasDuplicates(array) {
            return (new Set(array)).size !== array.length;
        }
    });
})(jQuery);