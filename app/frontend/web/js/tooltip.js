$(document).ready(function() {
    $('[data-toggle=\"tooltip\"]').tooltip({
        placement: 'top',  /* Adjust as per requirement: top, right, bottom, left */
        boundary: 'window',  /* Keeps the tooltip within the viewport */
        html: true
    });
});
