<?
define("PUBLIC_AJAX_MODE", true);
define("NO_AGENT_CHECK", true);
define("DisableEventsCheck", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

IncludeModuleLangFile(__FILE__);

$arParams = $_REQUEST['arParams'];

/*
$meetIblockID = isset($_REQUEST['IBLOCK_ID'])
	? intval($_REQUEST['IBLOCK_ID'])
	: (is_array($arParams)
		? intval($arParams['IBLOCK_ID'])
		: 0
	);
if ($meetIblockID <= 0)
	$meetIblockID = COption::GetOptionInt("intranet", "iblock_absence");
*/

$iblockID = isset($_REQUEST['IBLOCK_ID'])
	? intval($_REQUEST['IBLOCK_ID'])
	: (is_array($arParams)
		? intval($arParams['IBLOCK_ID'])
		: 0
	);
if ($iblockID <= 0)
	$iblockID = COption::GetOptionInt("intranet", "iblock_absence");

$bIblockChanged = $iblockID != COption::GetOptionInt('intranet', 'iblock_absence');

function AddAbsence($arFields)
{
	global $DB, $iblockID;

	if (CModule::IncludeModule('iblock'))
	{
		$PROP = array();

		$element = new CIBlockElement;

		if (!empty($arFields['ACTIVE_FROM']) && !empty($arFields['ACTIVE_TO']))
		{
			if ($DB->isDate($arFields['ACTIVE_FROM'], false, LANG, 'FULL') && $DB->isDate($arFields['ACTIVE_TO'], false, LANG, 'FULL'))
			{
				if (makeTimeStamp($arFields['ACTIVE_FROM']) > makeTimeStamp($arFields['ACTIVE_TO']))
					$element->LAST_ERROR .= getMessage('INTR_ABSENCE_FROM_TO_ERR').'<br>';
			}
		}

		if (empty($element->LAST_ERROR))
		{
			$db_absence = CIBlockProperty::GetList(Array(), Array("CODE"=>"MEET_LIST", "IBLOCK_ID"=>$iblockID));
			if ($ar_absence = $db_absence->Fetch())
			{
				$PROP[$ar_absence['ID']] = array($arFields["MEET_LIST"]);
			}

			$db_user = CIBlockProperty::GetList(Array(), Array("CODE"=>"MEET", "IBLOCK_ID"=>$iblockID));
			if ($ar_user = $db_user->Fetch())
			{
				$PROP[$ar_user['ID']] = array($arFields["MEET"]);
			}

			$arNewFields = array(
				"NAME" => $arFields["NAME"],
				"PROPERTY_VALUES"=> $PROP,
				"ACTIVE_FROM" => $arFields["ACTIVE_FROM"],
				"ACTIVE_TO" => $arFields["ACTIVE_TO"],
				"IBLOCK_ID" => $iblockID
			);

			$ID = $element->Add($arNewFields);
		}
	}
	if (empty($ID))
	{
		$arErrors = preg_split("/<br>/", $element->LAST_ERROR);
		return $arErrors;
	}
	else
	{
		return $ID;
	}
}
function EditAbsence($arFields)
{
	global $DB, $iblockID;

	if (CModule::IncludeModule('iblock'))
	{
		$PROP = array();

		$element = new CIBlockElement;

		if (!empty($arFields['ACTIVE_FROM']) && !empty($arFields['ACTIVE_TO']))
		{
			if ($DB->isDate($arFields['ACTIVE_FROM'], false, LANG, 'FULL') && $DB->isDate($arFields['ACTIVE_TO'], false, LANG, 'FULL'))
			{
				if (makeTimeStamp($arFields['ACTIVE_FROM']) > makeTimeStamp($arFields['ACTIVE_TO']))
					$element->LAST_ERROR .= getMessage('INTR_ABSENCE_FROM_TO_ERR').'<br>';
			}
		}

		if (empty($element->LAST_ERROR))
		{
			$db_absence = CIBlockProperty::GetList(Array(), Array("CODE"=>"MEET_LIST", "IBLOCK_ID"=>$iblockID));
			if ($ar_absence = $db_absence->Fetch())
			{
				$PROP[$ar_absence['ID']] = array($arFields["MEET_LIST"]);
			}

			$db_user = CIBlockProperty::GetList(Array(), Array("CODE"=>"MEET", "IBLOCK_ID"=>$iblockID));
			if ($ar_user = $db_user->Fetch())
			{
				$PROP[$ar_user['ID']] = array($arFields["MEET"]);
			}

			$arNewFields = array(
				"NAME" => $arFields["NAME"],
				"PROPERTY_VALUES"=> $PROP,
				"ACTIVE_FROM" => $arFields["ACTIVE_FROM"],
				"ACTIVE_TO" => $arFields["ACTIVE_TO"],
				"IBLOCK_ID" => $iblockID
			);

			$ID = $element->Update(intval($arFields["absence_element_id"]), $arNewFields);
		}
	}
	if (empty($ID))
	{
		$arErrors = preg_split("/<br>/", $element->LAST_ERROR);
		return $arErrors;
	}
	else
	{
		return $ID;
	}
}

function DeleteAbsence($absenceID)
{
	if (CModule::IncludeModule('iblock'))
	{
		CIBlockElement::Delete(intval($absenceID));
	}
}

if(!CModule::IncludeModule('iblock'))
{
	echo GetMessage("INTR_ABSENCE_BITRIX24_MODULE");
}
else
{
	if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"]) && $_GET["action"] == "delete" && check_bitrix_sessid())
	{
		if(CIBlockElementRights::UserHasRightTo($iblockID, intval($_GET["absenceID"]), "element_delete"))
			DeleteAbsence($_GET["absenceID"]);
		die();
	}

	$ID = 1;
	if($_SERVER["REQUEST_METHOD"] === "POST" && check_bitrix_sessid())
	{
		if (isset($_POST['absence_element_id']) && CIBlockElementRights::UserHasRightTo($iblockID, intval($_POST['absence_element_id']), 'element_edit'))
		{
			$ID = EditAbsence($_POST);
		}
		elseif(!isset($_POST['absence_element_id']) && CIBlockSectionRights::UserHasRightTo($iblockID, 0, "section_element_bind"))
		{
			$ID = AddAbsence($_POST);
		}
		else
		{
			die('error:<li>'.GetMessage('INTR_USER_ERR_NO_RIGHT').'</li>');
		}

		if(is_array($ID))
		{
			$arErrors = $ID;
			foreach ($arErrors as $key => $val)
			{
				if ($val == '')
					unset($arErrors[$key]);
			}
			$ID = 0;
			die('error:<li>'.implode('</li><li>', array_map('htmlspecialcharsbx', $arErrors))).'</li>';
		}
		elseif (isset($_POST['absence_element_id']))
			die("close");
	}
?><div style="width: 450px; "><?

	if ($ID>1)
	{
	?>

	<p><?=GetMessage("INTR_ABSENCE_SUCCESS")?></p>
	<form method="POST" action="<?echo BX_ROOT."/tools/intranet_meropriyatiya.php".($bIblockChanged?"?IBLOCK_ID=".$iblockID:"")?>" id="ABSENCE_FORM">
		<input type="hidden" name="reload" value="Y">
	</form><?
	}
	else
	{
		$arElement = array();
		if (isset($arParams["ABSENCE_ELEMENT_ID"]) && intval($arParams["ABSENCE_ELEMENT_ID"])>0)
		{
			$rsElement = CIBlockElement::GetList(array(), array("ID" => intval($arParams["ABSENCE_ELEMENT_ID"]), "IBLOCK_ID" => $iblockID), false, false, array("ID", "NAME", "ACTIVE_FROM", "ACTIVE_TO", "IBLOCK_ID", "PROPERTY_ABSENCE_TYPE", "PROPERTY_USER"));
			$arElement = $rsElement->Fetch();
		}

		$controlName = "Single_" . RandString(6);
	?>
	<form method="POST" action="<?echo BX_ROOT."/tools/intranet_meropriyatiya.php"?>" id="ABSENCE_FORM">
		<?if (isset($_POST['absence_element_id']) || isset($arElement["ID"])):?>
		<input type="hidden" value="<?=(isset($_POST['absence_element_id'])) ? htmlspecialcharsbx($_POST['absence_element_id']) : $arElement['ID']?>" name="absence_element_id"><?
		endif;?>
<?
if ($bIblockChanged):
?>
		<input type="hidden" name="IBLOCK_ID" value="<?=$iblockID?>" />
<?
endif;
?>

		<table width="100%" cellpadding="5">
		<tr id="tr_PROPERTY_70">
			<td class="adm-detail-valign-top" width="40%">		<div style="font-size:14px;font-weight:bold;padding-bottom:8px"><label for="MEET"><?=GetMessage("INTR_ABSENCE_USER")?></label></div>
			<input name="MEET" id="PROP[70][n0]" value="" size="5" type="text"><input type="button" value="..." onClick="jsUtils.OpenWindow('/bitrix/admin/iblock_element_search.php?lang=ru&amp;IBLOCK_ID=20&amp;n=PROP[70]&amp;k=n0&amp;iblockfix=y&amp;tableId=iblockprop-E-70-20', 900, 700);">&nbsp;<span id="sp_41aaa02ff6f7ff5466577d52fc0f4149_n0"></span><script type="text/javascript">
var MV_41aaa02ff6f7ff5466577d52fc0f4149 = 1;
function InS41aaa02ff6f7ff5466577d52fc0f4149(id, name){ 
	oTbl=document.getElementById('tb41aaa02ff6f7ff5466577d52fc0f4149');
	oRow=oTbl.insertRow(oTbl.rows.length-1); 
	oCell=oRow.insertCell(-1); 
	oCell.innerHTML='<input name="PROP[70][n'+MV_41aaa02ff6f7ff5466577d52fc0f4149+']" value="'+id+'" id="PROP[70][n'+MV_41aaa02ff6f7ff5466577d52fc0f4149+']" size="5" type="text">'+
'<input type="button" value="..." '+
'onClick="jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang=ru&amp;IBLOCK_ID=20&amp;n=PROP[70]&amp;k=n'+MV_41aaa02ff6f7ff5466577d52fc0f4149+'&amp;iblockfix=y&amp;tableId=iblockprop-E-70-20\', '+
' 900, 700);">'+'&nbsp;<span id="sp_41aaa02ff6f7ff5466577d52fc0f4149_n'+MV_41aaa02ff6f7ff5466577d52fc0f4149+'" >'+name+'</span>';MV_41aaa02ff6f7ff5466577d52fc0f4149++;}
</script>
	</td>
		</tr>
			
			<tr valign="bottom">
				<td>
					<div style="font-size:14px;font-weight:bold;padding-bottom:8px"><label for="MEET_LIST"><?=GetMessage("INTR_ABSENCE_TYPE")?></label></div>
					<select name="MEET_LIST" id="absence_type" style="width:100%;font-size:14px;border:1px #c8c8c8 solid;">
						<option value="0"><?=GetMessage("INTR_ABSENCE_NO_TYPE")?></option>
						<?
						$property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$iblockID, "CODE"=>"MEET_LIST"));
						while($enum_fields = $property_enums->Fetch())
						{
							?><option value="<?=$enum_fields['ID'] ?>"
								<? if (isset($_POST['MEET_LIST']) && $_POST['MEET_LIST'] == $enum_fields['ID'] || isset($arElement['PROPERTY_ABSENCE_TYPE_ENUM_ID']) && $arElement['PROPERTY_ABSENCE_TYPE_ENUM_ID'] == $enum_fields['ID']) echo 'selected'; ?>>
								<?=htmlspecialcharsbx(Bitrix\Intranet\UserAbsence::getTypeCaption($enum_fields['XML_ID'], $enum_fields['VALUE'])) ?>
							</option><?
						}
						?>
					</select>
				</td>
			</tr>
			<tr valign="bottom">
				<td>
					<div style="font-size:14px;font-weight:bold;padding-bottom:8px"><label for="NAME"><?=GetMessage("INTR_ABSENCE_NAME")?></label></div>
					<input type="text" value="<?if (isset($_POST['NAME'])) echo htmlspecialcharsbx($_POST['NAME']); elseif (isset($arElement["NAME"])) echo htmlspecialcharsbx($arElement["NAME"]);?>" name="NAME" id="NAME" style="width:100%;font-size:14px;border:1px #c8c8c8 solid;">
				</td>
			</tr>
			<tr>
				<td>
					<div style="font-size:14px;font-weight:bold;padding-bottom:8px"><?=GetMessage("INTR_ABSENCE_PERIOD")?></div>
				</td>
			</tr>
			<tr valign="bottom" >
				<td>
					<table cellpadding="0" cellspacing="0" border="0">
						<tr>
							<td  width="100px">
								<label for="ACTIVE_FROM"><?=GetMessage("INTR_ABSENCE_ACTIVE_FROM")?></label>
							</td>
							<td>
								<?
								$input_value_from = "";
								if (isset($arElement["ACTIVE_FROM"]) || isset($_POST["ACTIVE_FROM"]))
									$input_value_from = (isset($arElement["ACTIVE_TO"])) ? htmlspecialcharsbx(FormatDateFromDB($arElement["ACTIVE_FROM"])) : htmlspecialcharsbx(FormatDateFromDB($_POST["ACTIVE_FROM"]));
								$APPLICATION->IncludeComponent("bitrix:main.calendar","",Array(
									"SHOW_INPUT" => "Y",
									"FORM_NAME" => "",
									"INPUT_NAME" => "ACTIVE_FROM",
									"INPUT_VALUE" => $input_value_from,
									"SHOW_TIME" => "Y",
									"HIDE_TIMEBAR" => "Y"
									)
								);?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr valign="bottom">
				<td>
					<table cellpadding="0" cellspacing="0" border="0">
						<tr>
							<td width="100px">
								<label for="ACTIVE_TO"><?=GetMessage("INTR_ABSENCE_ACTIVE_TO")?></label>
							</td>
							<td>
							<?
							$input_value_to = "";
							if (isset($arElement["ACTIVE_TO"]) || isset($_POST["ACTIVE_TO"]))
								$input_value_to = (isset($arElement["ACTIVE_TO"])) ? htmlspecialcharsbx(FormatDateFromDB($arElement["ACTIVE_TO"])) : htmlspecialcharsbx(FormatDateFromDB($_POST["ACTIVE_TO"]));
							$APPLICATION->IncludeComponent("bitrix:main.calendar","",Array(
								"SHOW_INPUT" => "Y",
								"FORM_NAME" => "",
								"INPUT_NAME" => "ACTIVE_TO",
								"INPUT_VALUE" => $input_value_to,
								"SHOW_TIME" => "Y",
								"HIDE_TIMEBAR" => "Y"
								)
							);?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<?echo bitrix_sessid_post()?>
	</form>
<?
	}
?>
	<script type="text/javascript">
		var myBX;
		if(window.BX)
			myBX = window.BX;
		else if (window.top.BX)
			myBX = window.top.BX;
		else
			myBX = null;

		var myPopup = myBX.AbsenceCalendar.popup;
		var myButton = myPopup.buttons[0];
		<?if(isset($arParams["ABSENCE_ELEMENT_ID"]) && intval($arParams["ABSENCE_ELEMENT_ID"])>0 || isset($_POST['absence_element_id'])):?>
			myButton.setName('<?=\CUtil::jsEscape(getMessage('INTR_ABSENCE_EDIT')) ?>');
			myPopup.setTitleBar('<?=\CUtil::jsEscape(getMessage('INTR_EDIT_TITLE')) ?>');
		<?elseif ($ID > 1):?>
			myButton.setName('<?=\CUtil::jsEscape(getMessage('INTR_ABSENCE_ADD_MORE')) ?>');
			myPopup.setTitleBar('<?=\CUtil::jsEscape(getMessage('INTR_ADD_TITLE')) ?>');
		<?else:?>
			myButton.setName('<?=\CUtil::jsEscape(getMessage('INTR_ABSENCE_ADD')) ?>');
			myPopup.setTitleBar('<?=\CUtil::jsEscape(getMessage('INTR_ADD_TITLE')) ?>');
		<?endif?>

		myPopup = null;
		myButton = null;
		myBX = null;
	</script>
</div>
<?
}
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>
