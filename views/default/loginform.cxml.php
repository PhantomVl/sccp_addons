<?php
$URL_srv = $this->xml_url;
//$URL_srv = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$PAGE_Prefix = $this-> page_title;
?>
<?xml version="1.0" encoding="utf-8" ?>
<CiscoIPPhoneInput>
  <Title><?php echo $PAGE_Prefix;?> Login</Title>
  <Prompt>User Login</Prompt>
  <URL><?php echo $URL_srv;?>&amp;action=login</URL>
  <InputItem>
    <DisplayName>Name</DisplayName>
    <QueryStringParam>userid</QueryStringParam>
    <InputFlags>N</InputFlags>
  </InputItem>
  <InputItem>
    <DisplayName>Pin</DisplayName>
    <QueryStringParam>pincode</QueryStringParam>
    <InputFlags>NP</InputFlags>
  </InputItem>
</CiscoIPPhoneInput>
