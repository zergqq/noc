/**
 * Created by Nfq on 0025 2015-01-25.
 */
$(document).ready(function()
{
    $('#categories a.delete').click(function()
    {
        if (confirm("Are you sure you want to delete this category?"))
        {
            var id = $(this).parent().attr('id');
            var data = 'id=' + id ;
            var parent = $(this).parent();
            $.ajax(
                {
                    type: "POST",
                    url: "ajax.php",
                    data: data,
                    cache: false,

                    success: function()
                    {
                        parent.fadeOut('slow', function() {$(this).remove();});
                    }

                });

        }
    });

});