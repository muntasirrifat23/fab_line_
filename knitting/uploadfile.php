<!DOCTYPE html>  
<html>  
   <head>  
        <script src="jquery.min.js"></script>
        <script type="text/javascript">
         setInterval('onUploadFile()', 300000);
         function onUploadFile()
         {
            $.ajax({
                        type:'POST',
                        url:'ajaxUploadFile.php' ,
                        success:function(html)
                         {  $('#upFile').html(html); } 

                   }) ; 
         }
        </script>
   </head>
   <body> 
        <div id="upFile">Under Construction</div>
   </body>
</html>