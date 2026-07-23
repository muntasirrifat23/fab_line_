<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" type="text/css" href="css/w3.css">
    </link>
    <script src="jquery.min.js"></script>
    <script language="javascript" type="text/javascript">
    function onSave() {
        $.ajax({
            type: 'POST',
            url: 'ajaxUploadFile.php',
            success: function(html) {
                $('#upFile').html(html);
            }
        });
    }
    </script>
</head>

<!-- MainBody -->
<body>
    <div id="upFile">Under Construction</div>
    <div class="w3-panel ">
        <input type="button" class="w3-button w3-black" value="TEST UPLOAD-SAVE" style="width:100%;height:50px;"
            onclick="onSave()">
    </div>

</body>
<!-- End-MainBody -->

</html>