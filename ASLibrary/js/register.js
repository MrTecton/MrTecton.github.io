$(document).ready(function () {
    $("#btn-register").click(function () {
        if(register.validateRegistration() === true) {
            var regMail     = $("#reg-email").val(),
                regUser     = $("#reg-username").val(),
                regPass     = $("#reg-password").val(),
                regPassConf = $("#reg-repeat-password").val(),
                regBotSsum  = $("#reg-bot-sum").val();

            var data = { 
                userData: {
                    email           : regMail,
                    username        : regUser,
                    password        : regPass,
                    confirm_password: regPassConf,
                    bot_sum         : regBotSsum
                },
                fieldId: {
                    email           : "reg-email",
                    username        : "reg-username",
                    password        : "reg-password",
                    confirm_password: "reg-repeat-password",
                    bot_sum         : "reg-bot-sum"
                }
            };
            
            register.registerUser(data);
        }                        
    });
});

var register = {};

register.registerUser = function (data) {
    var btn = $("#btn-register");
    
    asengine.loadingButton(btn, $_lang.creating_account);
    
    data.userData.password = CryptoJS.SHA512(data.userData.password).toString();
    data.userData.confirm_password = CryptoJS.SHA512(data.userData.confirm_password).toString();
    
    $.ajax({
        url: "ASEngine/ASAjax.php",
        type: "POST",
        data: {
            action  : "registerUser",
            user    : data
        },
        success: function (result) {
            asengine.removeLoadingButton(btn);

            console.log(result);
            
            var res = JSON.parse(result);
            
            if(res.status === "error") {
                for(var i=0; i<res.errors.length; i++) {
                    var error = res.errors[i];
                    asengine.displayErrorMessage($("#"+error.id), error.msg);
                }
            }
            else {
                asengine.displaySuccessMessage($(".register-form fieldset"), res.msg);
            }
        }
    });
};

register.validateRegistration = function () {
    var valid = true;
    
    asengine.removeErrorMessages();
    
    $(".register-form").find("input").each(function () {
        var el = $(this);

        if($.trim(el.val()) === "") {
            asengine.displayErrorMessage(el);
            valid = false;
        }
    });

    var regMail     = $("#reg-email"),
        regPass     = $("#reg-password"),
        regPassConf = $("#reg-repeat-password");
    
    if(!asengine.validateEmail(regMail.val()) && regMail.val() != "") {
        valid = false;
        asengine.displayErrorMessage(regMail,$_lang.email_wrong_format);
    }

    if(regPass.val() !== regPassConf.val() && regPass.val() != "" && regPassConf.val() != "") {
        valid = false;
        asengine.displayErrorMessage(regPassConf, $_lang.passwords_dont_match);
    }

    return valid;
};