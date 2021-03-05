// Set a timeout to close an alert automatically.
function timeout(alert) {
    setTimeout(function () {
        alert.find('.close-alert').click();
    }, 3000);
}

// Close an alert and show the next one, if it is there.
function closeAlert(alert) {
    alert.fadeOut(300, function () {
        var index = alert.attr('data-index');
        alert.remove();

        if (jQuery('.alert').length > 0) {
            var next = jQuery('.alert[data-index="' + (parseInt(index) + 1).toString() + '"]');
            next.show();

            timeout(next);
        }
    });
}

jQuery(document).ready(function () {
    // Bind function closeAlert() to the click event of close buttons in alerts.
    jQuery('.close-alert').click(function () {
        closeAlert(jQuery(this).parent());
    });

    var first = jQuery('.alert[data-index="0"]');
    first.show();
    // Call closeAlert(), for the first alert in queue, after 3 seconds since the document is ready.
    timeout(first);
});