var asengine = {};

asengine.loadingButton = function(button, loadingText) {
    oldText = button.text();
    button.attr("rel",oldText)
          .text(loadingText)
          .addClass("disabled")
          .attr('disabled', "disabled");
};

asengine.removeLoadingButton = function (button) {
    var oldText = button.attr('rel');
    button.text(oldText)
                     .removeClass("disabled")
                     .removeAttr("disabled")
                     .removeAttr("rel");
};

asengine.displaySuccessMessage = function (parentElement, message) {
    $(".alert-success").remove();
    var div = ("<div class='alert alert-success'>"+message+"</div>");
    parentElement.append(div);
};

asengine.displayErrorMessage = function(element, message) {
    var controlGroup = element.parents(".control-group");
    controlGroup.addClass("error").addClass("has-error");
    if(typeof message !== "undefined") {
        var helpBlock = $("<span class='help-inline text-error'>"+message+"</span>");
        controlGroup.find(".controls").append(helpBlock);
    }
};

asengine.removeErrorMessages = function () {
    $(".control-group").removeClass("error").removeClass("has-error");
    $(".help-inline").remove();
};

asengine.validateEmail = function (email) {
  var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return regex.test(email);
};

asengine.urlParam = function(name){
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null;
};




