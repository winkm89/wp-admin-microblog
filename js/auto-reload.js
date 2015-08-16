// This is the auto reload script which searches for new messages
 jQuery(document).ready(function($) {
    $.ajaxSetup({ cache: false });
    setInterval(function() {
        $.get(wpam_plugin_url + "?p_id=" + wpam_latest_message_id, 
        function(text){
            if ( text !== '' ) {
                var ret;
                var current = $('#wpam_table_messages_headline').next();
                current.attr('id', 'new_messages_info');
                ret = ret + '<td id="wpam-new-messages-info" colspan="4"><a href="" title="' + wpam_i18n_refresh + '">' + text + '</a></td>';
                current.html(ret);
            }
        });
    }, wpam_auto_reload_interval);
});


