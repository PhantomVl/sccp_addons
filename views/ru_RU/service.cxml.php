<?php
$URL_srv = $this->xml_url;
$PAGE_Prefix = $this-> page_title;
?>
<?xml version="1.0" encoding="utf-8" ?>
<CiscoIPPhoneMenu>
  <Title><?php echo $PAGE_Prefix;?> Сервис</Title>
  <MenuItem>
      <Name>Вход</Name>
      <URL><?php echo $URL_srv;?>&amp;action=loginform</URL>
  </MenuItem>
  <?php if ($this ->dev_login !== false) {
    echo '<MenuItem><Name>Покинуть устройство</Name><URL>'.$URL_srv.'&amp;action=logout</URL></MenuItem>';
   }?>
  <SoftKeyItem>
   <Name>Select</Name>
   <URL>SoftKey:Select</URL>
   <Position>1</Position>
  </SoftKeyItem>

  <SoftKeyItem>
      <Name>Exit</Name>
      <URL>SoftKey:Exit</URL>
      <Position>3</Position>
  </SoftKeyItem>

  <SoftKeyItem>
      <Name>Services</Name>
      <URL>Init:Services</URL>
<?php //      <URL>Init:Directories</URL>   ?>  
      <Position>4</Position>
  </SoftKeyItem>
</CiscoIPPhoneMenu>