<?php
$URL_srv = $this->xml_url;
//$URL_srv = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$PAGE_Prefix = $this-> page_title;
?>
<?xml version="1.0" encoding="utf-8" ?>
<CiscoIPPhoneInput>
  <Title><?php echo $PAGE_Prefix;?> Авторизация</Title>
  <Prompt>Ваша Учетная запись</Prompt>
  <URL><?php echo $URL_srv;?>&amp;action=login</URL>
  <InputItem>
    <DisplayName>Аб.Номер</DisplayName>
    <QueryStringParam>userid</QueryStringParam>
    <InputFlags>N</InputFlags>
  </InputItem>
  <InputItem>
    <DisplayName>ПинКод</DisplayName>
    <QueryStringParam>pincode</QueryStringParam>
    <InputFlags>NP</InputFlags>
  </InputItem>
</CiscoIPPhoneInput>
