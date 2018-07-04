$(document).ready(function () {
    
    $("#comment").click(function () {
        asengine.removeErrorMessages();
        
        var comment = $("#comment-text"),
             btn    = $(this);
             
        if($.trim(comment.val()) == "") {
            asengine.displayErrorMessage(comment, $_lang.field_required);
            return;
        }
        
        asengine.loadingButton(btn, $_lang.posting);
        
         $.ajax({
            url: "ASEngine/ASAjax.php",
            type: "POST",
            data: {
                action : "postComment",
                comment: comment.val()
            },
            success: function (result) {
                asengine.removeLoadingButton(btn);
                try {
                   var res = JSON.parse(result);
                   
                   var html  = "<blockquote>";
                        html += "<p>"+res.comment+"</p>";
                        html += "<small>"+res.user+" <em> "+ $_lang.at +res.postTime+"</em></small>";
                        html += "</blockquote>";
                    if( $(".comments-comments blockquote").length >= 7 )
                        $(".comments-comments blockquote").last().remove();
                    $(".comments-comments").prepend($(html));
                    comment.val("");
                }
                catch(e){
                   asengine.displayErrorMessage(comment, $_lang.error_writing_to_db);
                }
            }
        });
    });
	
});
