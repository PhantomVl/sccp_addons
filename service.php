<?php 
  header("Content-type: text/xml;charset=utf-8");  
  include "service.class.php";
//  include "freepbx.conf";

  $spage = new cisco\service();
  if (empty($spage->class_error)) {
    $spage->request_processing();
    
    $display_page = $spage->ServiceShowPage();
    
    foreach($display_page as $key => $page) {
         echo $page['content'];
    }
  } else {
    print_r("<br> Request:<br><pre>");
    print_r("<br>END");
    print("</pre>");
}

?>