// Make it possible to use the wordpress media uploader
jQuery(document).ready(function() {
    jQuery('#upload_image_button').click(function() {
        formfield = jQuery('#wpam_nm_text').attr('name');
        tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
        return false;
    });
    window.send_to_editor = function(html) {
        imgurl = jQuery(html).attr('href');
        old = jQuery('#wpam_nm_text').val();
        imgurl = imgurl.replace("http://","file://");
        imgurl = imgurl.replace("https://","file://");
        jQuery('#wpam_nm_text').val(old + imgurl);
        tb_remove();
    };
});