$(document).on('rex:ready', function (event, container) {
    if (tagsInput !== undefined) {
        [].forEach.call(document.querySelectorAll('input[type="tags"]'), tagsInput);
    }

    $('.diff input[type="radio"]').on('change', function () {
        if ($(this).is(':checked')) {
            Cookies.set('diff_detect_' + $(this).attr('name'), $(this).val(), {
                expires: 365,
                sameSite: 'strict'
            });
        }
    });
});
