<?php
	global $PaymentSuccessfull;
	if($PaymentSuccessfull) {
?>
<html>
<script>
alert('<?php echo __("Thanks for your purchase. Please click the OK button to download the file.")?>');
// add relevant message above or remove the line if not required
window.onload = function(){
    if(window.opener){
         window.close();
     }
    else{
         if(top.dg.isOpen() == true){
             top.dg.closeFlow();
             return true;
          }
      }
      top.window.opener.location = "<?php echo $item_url;?>";
};
                                
</script>
</html>
		<?php
	}
	else
	{
	?>
<html>
<script>
alert('<?php echo __("System was not able to complete the payment.")?>');
// add relevant message above or remove the line if not required
window.onload = function(){
    if(window.opener){
         window.close();
     }
    else{
         if(top.dg.isOpen() == true){
             top.dg.closeFlow();
             return true;
          }
      }                              
};
                                
</script>
</html>
<?php 
	}
?>