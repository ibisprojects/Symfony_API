$("#allowaccess").click(function() {
    $("#autorizeForm").append("<input type='hidden' name='approve' value='approve'/>");
    $("#autorizeForm").append("<input type='hidden' name='deny' value=''/>");
    $("#autorizeForm").submit();
});
$("#denyaccess").click(function() {
    $("#autorizeForm").append("<input type='hidden' name='approve' value=''/>");
    $("#autorizeForm").append("<input type='hidden' name='deny' value='deny'/>");
    $("#autorizeForm").submit();
});


