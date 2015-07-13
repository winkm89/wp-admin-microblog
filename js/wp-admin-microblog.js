// for show/hide buttons
function wpam_showhide(where) {
    if (document.getElementById(where).style.display != "block") {
    	document.getElementById(where).style.display = "block";
    }
    else {
    	document.getElementById(where).style.display = "none";
    }
}
// for show replies
function wpam_showAllReplies(id, number) {
    name = "wpam-reply-sum-" + id;
    document.getElementById(name).style.display = "none";
    name = "wpam-reply-" + id;
    for (i=1; i<= number; i++) {
         name2 = name + "-" + i;
         document.getElementById(name2).style.display = "";
    }
}
// for editing messages
function wpam_editMessage(post_ID) {
    var parent = "wp_admin_blog_message_" + post_ID;
    var message_text_field = "wp_admin_blog_message_text_" + post_ID;
    var textarea = "wp_admin_blog_edit_text";
    var text;

    if (isNaN(document.getElementById(textarea))) {
    }
    else {
        var reg = /<(.*?)>/g;
        text = document.getElementById(message_text_field).value;
        text = text.replace( reg, "" );
        // create div
        var editor = document.createElement('div');
        editor.id = "div_edit";
        // create hidden fields
        var field_neu = document.createElement('input');
        field_neu.name = "wp_admin_blog_message_ID";
        field_neu.type = "hidden";
        field_neu.value = post_ID;
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
        save_button.value = "Save";
        save_button.type = "submit";
        save_button.className = "button-primary";
        // create cancel button
        var cancel_button = document.createElement('input');
        cancel_button.value = "Cancel";
        cancel_button.type = "button";
        cancel_button.className = "button";
        cancel_button.onclick = function () { document.getElementById(parent).removeChild(editor);};
        document.getElementById(parent).appendChild(editor);
        document.getElementById("div_edit").appendChild(field_neu);
        document.getElementById("div_edit").appendChild(textarea_neu);
        document.getElementById("div_edit").appendChild(save_button);
        document.getElementById("div_edit").appendChild(cancel_button);
    }
}
// for replies
function wpam_replyMessage(post_ID, parent_ID, reply, author) {
    var parent = "wp_admin_blog_message_" + post_ID;
    var textarea = "wp_admin_blog_edit_text";

    if (isNaN(document.getElementById(textarea))) {
    }
    else {
        // create div
        var editor = document.createElement('div');
        editor.id = "div_reply";
        // create hidden fields
        var field_neu = document.createElement('input');
        field_neu.name = "wp_admin_blog_parent_ID";
        field_neu.type = "hidden";
        field_neu.value = parent_ID;
        // create textarea
        var textarea_neu = document.createElement('textarea');
        textarea_neu.id = textarea;
        textarea_neu.name = textarea;
        textarea_neu.rows = 6;
        textarea_neu.style.width = "100%";
        if (reply == "true") {
            textarea_neu.value = "@" + author + " ";
        }
        // create button
        var save_button = document.createElement('input');
        save_button.name = "wp_admin_blog_reply_message_submit";
        save_button.value = "Submit";
        save_button.type = "submit";
        save_button.className = "button-primary";
        // create cancel button
        var cancel_button = document.createElement('input');
        cancel_button.value = "Cancel";
        cancel_button.type = "button";
        cancel_button.className = "button";
        cancel_button.onclick = function () { document.getElementById(parent).removeChild(editor);};
        document.getElementById(parent).appendChild(editor);
        document.getElementById("div_reply").appendChild(field_neu);
        document.getElementById("div_reply").appendChild(textarea_neu);
        document.getElementById("div_reply").appendChild(save_button);
        document.getElementById("div_reply").appendChild(cancel_button);
    }
}