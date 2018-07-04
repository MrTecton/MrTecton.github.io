$(document).ready(function () {
 	$(".form-horizontal").submit(function () {
    	return false;
    });
 	
    $("#btn-forgot-password").click(function () {
        var email = $("#forgot-password-email"),
            valid = true;

        asengine.removeErrorMessages();

        if($.trim(email.val()) === "") {
            valid = false;
            asengine.displayErrorMessage(email);
        }

        if(!asengine.validateEmail(email.val())) {
            valid = false;
            asengine.displayErrorMessage(email, $_lang.email_wrong_format);
        }

        if(valid)
            passres.forgotPassword(email.val());

    });
    
    
    $("#btn-reset-pass").click(function () {
        var np    = $("#password-reset-new-password"),
        valid = true;
        
        if($.trim(np.val()) === "") {
            valid = false;
            asengine.displayErrorMessage(np);
        }

        if($.trim(np.val()).length < 5) {
            valid = false;
            asengine.displayErrorMessage(np, $_lang.password_length);
        }

        if(valid)
            passres.resetPassword(np.val());
        });
                    
});

var passres = {};

passres.resetPassword = function (newPass) {
    var btn = $("#btn-reset-pass");
    
    asengine.loadingButton(btn, $_lang.resetting);
    
    var pass = CryptoJS.SHA512(newPass).toString();
    
    var key  = asengine.urlParam("k");
    
    $.ajax({
        url: "ASEngine/ASAjax.php",
        type: "POST",
        data: {
            action  : "resetPassword",
            newPass : pass,
            key     : key
        },
        success: function (result) {

            if ( result == '' )
            {
                asengine.displaySuccessMessage(
                    $("#password-reset-form fieldset"), 
                    $_lang.password_updated_successfully_login
                );
            }
            else
            {   
                asengine.displayErrorMessage(
                    $("#password-reset-new-password"), 
                    result
                );
            }

            asengine.removeLoadingButton(btn);
        }
    });
};

passres.forgotPassword = function (userEmail) {
    var btn = $("#btn-forgot-password");
    
    asengine.loadingButton(btn, $_lang.working);
    
    $.ajax({
        url: "ASEngine/ASAjax.php",
        type: "POST",
        data: {
            action  : "forgotPassword",
            email : userEmail
        },
        success: function (result) {
            try {
                
                if(result == '')
                    asengine.displaySuccessMessage(
                            $("#forgot-pass-form fieldset"),
                            $_lang.password_reset_email_sent
                        );
                else {
                    var errors = JSON.parse(result);
                    asengine.displayErrorMessage(
                        $("#forgot-password-email"),
                        errors[0]
                    );
                }
            }
            catch (err) {
                  asengine.displayErrorMessage(
                        $("#forgot-password-email"),
                        $_lang.message_couldnt_be_sent
                    );
            }

            asengine.removeLoadingButton(btn);
           
        }
    });
};



