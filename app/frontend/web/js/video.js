$(document).ready(function() {
    let hash = window.location.hash;
    if (hash) {
        $('a[href="' + hash + '"]').tab('show');
    }

    $('a[data-bs-toggle="tab"]').on('click', function (e) {
        window.location.hash = e.target.hash;
    });
});
