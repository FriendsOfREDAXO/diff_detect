$(document).on('rex:ready', function (event, container) {
    setTimeout(function () {
        $('.rex-js-widget-media.rex-js-widget-preview').trigger('mouseenter');
    }, 100);

    if (tagsInput !== undefined) {
        [].forEach.call(document.querySelectorAll('input[type="tags"]'), tagsInput);
    }
});
