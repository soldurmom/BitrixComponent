<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$APPLICATION->RestartBuffer();
$this->IncludeComponentTemplate();

echo 'календарь <pre>';
print_r($arResult);
echo '</pre>';

?>