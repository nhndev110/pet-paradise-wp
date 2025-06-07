jQuery(document).ready(function($){
 
    $('.atlt-review-notice-dismiss button').click(function(){
        var prefix = $(this).closest('.atlt-review-notice-dismiss').data('prefix');
        var nonce = $(this).closest('.atlt-review-notice-dismiss').data('nonce');

        $.post(ajaxurl, {action: 'atlt_hide_review_notice', prefix: prefix, nonce: nonce}, (response)=>{
            $(this).closest('.cpt-review-notice').slideUp();
        });
    });
});