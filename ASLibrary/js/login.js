 $(document).ready(function () {
 	$(".form-horizontal").submit(function () {
    	return false;
    });
 	
    $("#btn-login").click(function () {
        var  un    = $("#login-username"),
             pa    = $("#login-password");

       if(login.validateLogin(un, pa) === true) {
            var data = {
                username: un.val(),
                password: pa.val(),
                id: {
                    username: "login-username",
                    password: "login-password"
                }
            };
            login.loginUser(data);
       }

    });

    $("#login-username").focus();
});

var login = {};

login.loginUser = function (data) {
    var btn = $("#btn-login");
    asengine.loadingButton(btn, $_lang.logging_in);

    data.password = CryptoJS.SHA512(data.password).toString();

    $.ajax({
        url: "ASEngine/ASAjax.php",
        type: "POST",
        data: {
            action  : "checkLogin",
            username: data.username,
            password: data.password,
            id      : data.id
        },
        success: function (result) {
           asengine.removeLoadingButton(btn);
           if(result === "true")
               window.location = SUCCESS_LOGIN_REDIRECT;
           else {
               asengine.displayErrorMessage($("#login-username"));
               asengine.displayErrorMessage($("#login-password"), result);
           }

        }
    });
};

login.validateLogin = function (un, pass) {
    var valid = true;

    asengine.removeErrorMessages();

    if($.trim(un.val()) == "") {
        asengine.displayErrorMessage(un);
        valid = false;
    }
    if($.trim(pass.val()) == "") {
        asengine.displayErrorMessage(pass);
        valid = false;
    }

    return valid;
};