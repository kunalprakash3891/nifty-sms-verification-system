
jQuery(document).ready(function($) {
    $('#add-blacklist-toggle').on('click', function(e) {
        e.preventDefault();
        $('#add-blacklist-form').slideToggle();
    });
});