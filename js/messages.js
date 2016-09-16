// the message function (edit, like, ...)
jQuery(document).ready(function($) {
    // Edit a message
    $(".wpam_message_edit").click( function(){
        var message_id = $(this).attr("message_id");
        $.get(wpam_ajax_url + "&edit_id=" + message_id, 
        function(text){
            $('<a class="button-secondary" style="cursor:pointer;" onclick="javascript:teachpress_del_node(' + "'#pub_info_" + message_id + "'" + ');">Close</a>').appendTo('#pub_details_' + message_id);
            var parent = "wp_admin_blog_message_" + message_id;
            var textarea = "wp_admin_blog_edit_text";
            var text;

            if (isNaN(document.getElementById(textarea))) {
            }
            else {
                var reg = /<(.*?)>/g;
                text = text.replace( reg, "" );
                // create div
                var editor = document.createElement('div');
                editor.id = "div_edit";
                // create hidden fields
                var field_neu = document.createElement('input');
                field_neu.name = "wp_admin_blog_message_ID";
                field_neu.type = "hidden";
                field_neu.value = message_id;
                // create textarea
                var textarea_neu = document.createElement('textarea');
                textarea_neu.id = textarea;
                textarea_neu.name = textarea;
                textarea_neu.value = text;
                textarea_neu.rows = 8;
                textarea_neu.style.width = "100%";
                // create button
                var save_button = document.createElement('input');
                save_button.name = "wp_admin_blog_edit_message_submit";
                save_button.value = wpam_i18n_save_button;
                save_button.type = "submit";
                save_button.className = "button-primary";
                // create cancel button
                var cancel_button = document.createElement('input');
                cancel_button.value = wpam_i18n_cancel_button;
                cancel_button.type = "button";
                cancel_button.className = "button";
                cancel_button.onclick = function () { document.getElementById(parent).removeChild(editor);};
                document.getElementById(parent).appendChild(editor);
                document.getElementById("div_edit").appendChild(field_neu);
                document.getElementById("div_edit").appendChild(textarea_neu);
                document.getElementById("div_edit").appendChild(save_button);
                document.getElementById("div_edit").appendChild(cancel_button);
            }
        });
    });
    
    // Like a message or delete the like
    $(".wpam_message_like").click( function(){
        var message_id = $(this).attr("message_id");
        $.get(wpam_ajax_url + "&add_like_id=" + message_id, 
        function(text){
            // Get current like count
            var value = parseInt($("#wpam_like_" + message_id).text());
            
            // If the like was added
            if ( text === 'added' ) {
                $("#wpam_like_" + message_id).text(value + 1);
                $("#wpam_like_button_" + message_id).text(wpam_i18n_unlike_button);
                $("#wpam_like_button_" + message_id).removeClass("dashicons-star-filled");
                $("#wpam_like_button_" + message_id).addClass("dashicons-star-empty");
                if ( $("#wpam_like_" + message_id).hasClass("hidden") !== false ) {
                    $("#wpam_like_" + message_id).removeClass("hidden");
                }
            }
            
            // If the like was deleted
            if ( text === 'deleted' ) {
                $("#wpam_like_" + message_id).text(value - 1);
                $("#wpam_like_button_" + message_id).text(wpam_i18n_like_button);
                $("#wpam_like_button_" + message_id).removeClass("dashicons-star-empty");
                $("#wpam_like_button_" + message_id).addClass("dashicons-star-filled");
                if ( value - 1 === 0 ) {
                    $("#wpam_like_" + message_id).addClass("hidden");
                }
            }
        });
    });
    
    // Show all likes
    $(".wpam-like-star").each(function() {
        var $link = $(this);
        var $dialog = $('<div></div>')
            .load($link.attr('href') + ' #content')
            .dialog({
                    autoOpen: false,
                    title: wpam_i18n_like_title,
                    width: 500,
                    maxHeight: 600
            });

        $link.click(function() {
            $dialog.dialog('open');
            return false;
        });
    });
});


