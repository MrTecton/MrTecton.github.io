$(document).ready(function () {
	$(".no-submit").submit(function () {
    	return false;
    });
     $("#change_password").click(function () { 
        if(profile.validatePasswordUpdate())
            profile.updatePassword(); 
    });
    $("#update_details").click(function () {
        profile.updateDetails();
    });
});

var profile = {};

profile.updatePassword = function() {
        asengine.loadingButton($("#change_password"), $_lang.updating);
    
	var newPass = CryptoJS.SHA512($("#new_password").val()).toString();
	var oldPass = CryptoJS.SHA512($("#old_password").val()).toString();
        
	$.ajax({
		url: "ASEngine/ASAjax.php",
		type: "POST",
		data: {
			action	 : "updatePassword",
			oldpass  : oldPass,
			newpass  : newPass
		},
		success: function (result) {
            asengine.removeLoadingButton($("#change_password"));
                        
			if(result == "") {
				asengine.displaySuccessMessage(
                    $("#form-changepassword"),
                    $_lang.password_updated_successfully
                );
			}
			else {
				asengine.displayErrorMessage($("#old_password"), result);
			}
		}
	});
};

profile.validatePasswordUpdate = function () {
    
	asengine.removeErrorMessages();
	
	var oldpass  = $("#old_password"),
            newpass  = $("#new_password"),
            confpass = $("#new_password_confirm"),
            valid    = true;
		
	if($.trim(oldpass.val()) == "") {
		valid = false;
		asengine.displayErrorMessage(oldpass, $_lang.field_required);
	}
	
	if($.trim(newpass.val()) == "") {
		valid = false;
		asengine.displayErrorMessage(newpass, $_lang.field_required);
	}
	
	if($.trim(confpass.val()) == "") {
		valid = false;
		asengine.displayErrorMessage(confpass, $_lang.field_required);
	}
	
	if($.trim(confpass.val()) != $.trim(newpass.val()) ) {
		valid = false;
		asengine.displayErrorMessage(newpass);
		asengine.displayErrorMessage(confpass, $_lang.password_dont_match);
	}
	
	return valid;
	
};

profile.updateDetails = function () {
	asengine.removeErrorMessages();
        
        asengine.loadingButton($("#update_details"), $_lang.updating);
        
	var data = {
		action : "updateDetails",
		details: {
			first_name: $("#first_name").val(),
			last_name : $("#last_name").val(),
			address	  : $("#address").val(),
			phone	  : $("#phone").val()
		}
	};
        
	$.ajax({
		url: "ASEngine/ASAjax.php",
		type: "POST",
		data: data,
		success: function (result) {
                        asengine.removeLoadingButton($("#update_details"));
                        
			if(result == "") {
				asengine.displaySuccessMessage($("#form-details"),$_lang.details_updated);
			}
			else {
				console.log(result);
				asengine.displayErrorMessage($("#form-details input"));
				asengine.displayErrorMessage(
                    $("#phone"), 
                    $_lang.error_updating_db
                );
			}
		}
	});
};
