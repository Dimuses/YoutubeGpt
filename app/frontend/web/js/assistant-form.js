var maxFields = 10;
var addButton = $('.add-button');
var wrapper = $('.input-fields-wrap');
var x = 1;

$(addButton).click(function(e) {
    e.preventDefault();
    if (x < maxFields) {
        x++;
        $(wrapper).append('<div class="row"><div class="col-md-10"><input type="text" name="Assistant[settings][]" class="form-control"/></div><div class="col-md-2"><button class="remove-button btn btn-danger" style="width: 100%;">-</button></div></div>');
    }
});

$(wrapper).on('click', '.remove-button', function(e) {
    e.preventDefault();
    $(this).closest('.row').remove();
    x--;
});
