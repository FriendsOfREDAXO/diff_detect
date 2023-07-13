$(document).on('rex:ready', function (event, container) {
    if (tagsInput !== undefined) {
        [].forEach.call(document.querySelectorAll('input[type="tags"]'), tagsInput);
    }
});
