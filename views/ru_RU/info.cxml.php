<?php
$URL_srv = $this->xml_url;
$PAGE_Prefix = $this-> page_title;
$INFO = $this-> page_text;
?>
<?xml version="1.0" encoding="utf-8" ?>
<CiscoIPPhoneText>
  <Title><?php echo $PAGE_Prefix;?> ИНФО</Title>
  <Prompt>Информация</Prompt>
  <Text><?php echo $INFO;?></Text>  
  <SoftKeyItem>
      <Name>Exit</Name>
      <URL>SoftKey:Exit</URL>
      <Position>1</Position>
  </SoftKeyItem>
  <SoftKeyItem>
      <Name>Сервис</Name>
      <URL>Init:Services</URL>
      <Position>4</Position>
  </SoftKeyItem>

  
</CiscoIPPhoneText>
