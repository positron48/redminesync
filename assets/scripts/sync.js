$(document).ready(function () {
    $('#clone_issue_project').change(function () {
        $('.spinner-outer').show(0.2)
        $.ajax({
            method: "GET",
            url: "/api/project/" + $(this).val(),
            dataType: "json"
        })
        .done(function( data ) {
            var options = '<option value="">-</option>';
            $.each(data, function(key, value) {
                options += '<option value="' + value + '">' + key + '</option>';
            })
            $('#clone_issue_employer').html(options);
            $('.spinner-outer').hide(0.2)
        });
    })
})