jQuery(document).ready(function($) {
    // Function to handle form submission for adding and updating locations
    $('#gml-location-form').on('submit', function(e) {
        e.preventDefault();

        var data = {
            action: ($('#location-id').val() !== '') ? 'gml_update_location' : 'gml_add_location',
            label: $('#label').val(),
            address: $('#address').val(),
            url: $('#url').val(),
            lat: $('#lat').val(),
            lng: $('#lng').val()
        };

        if ($('#location-id').val() !== '') {
            data.id = $('#location-id').val();
        }
        console.log(data);
        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                // Handle success: reload page or update UI
                location.reload();
            } else {
                // Handle failure: display error message or alert 
                alert('Failed to add/update location: ' + response.data.message);
                console.error('Failed to add/update location: ' + response.data.message);
            }
        }).fail(function(xhr, textStatus, error) {
            // Handle AJAX request failure
            console.error('AJAX Error: ' + textStatus + ', ' + error);
            alert('AJAX Error: ' + textStatus + ', ' + error);
        });
    });

    // Function to handle edit button click
    $(document).on('click', '.edit-location', function() {
        var li = $(this).closest('li');
        $('#location-id').val(li.data('id'));
        $('#label').val(li.find('b').text());
        $('#address').val(li.find('.address').text());
        $('#url').val(li.find('.url').text());
        $('#lat').val(li.find('.lat').text());
        $('#lng').val(li.find('.lng').text());
    });

    // Function to handle delete button click
    $(document).on('click', '.delete-location', function() {
        var li = $(this).closest('li');
        var data = {
            action: 'gml_delete_location',
            id: li.data('id')
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                li.remove(); // Remove the list item from UI after successful deletion
            } else {
                alert('Failed to delete location.'); // Show error message if deletion fails
            }
        });
    });
});
