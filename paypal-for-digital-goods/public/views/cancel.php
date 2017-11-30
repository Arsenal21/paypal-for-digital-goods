<script>
alert('<?php echo __("Payment process has been cancelled on customer request.")?>'); 
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