$('#login-screen').submit(function(e){
    e.preventDefault();
    $('.alert').remove(); // Remove any existing alerts
    var btn = $(this).find('button[type="submit"]');
    btn.attr('disabled', true).html('Logging in...');
    
    $.ajax({
        url: 'ajax.php?action=login',
        method: 'POST',
        data: $(this).serialize(),
        error: function(err){
            console.log(err);
            btn.removeAttr('disabled').html('Get Started');
            $('#login-screen').prepend('<div class="alert alert-danger">Connection error occurred.</div>');
        },
        success: function(resp){
            if(resp == 1){
                location.href = 'dashboard.php?';
            } else {
                btn.removeAttr('disabled').html('Get Started');
                $('#login-screen').prepend('<div class="alert alert-danger">Username or password is incorrect.</div>');
            }
        }
    });
});