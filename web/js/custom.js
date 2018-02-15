$(function() {
    deleteConfirm = function (href) {
        bootbox.confirm('Вы уверены?', function (result) {
            if (result) {
                location.href = href;
            }
        });
    }

    $('.select2me').select2();
});