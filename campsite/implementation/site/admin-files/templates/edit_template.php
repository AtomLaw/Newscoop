<?php
require_once($GLOBALS['g_campsiteDir']. "/$ADMIN_DIR/templates/template_common.php");

if (!$g_user->hasPermission('ManageTempl') || !$g_user->hasPermission("DeleteTempl")) {
	camp_html_display_error(getGS("You do not have the right to modify templates."));
	exit;
}

$f_path = Input::Get('f_path', 'string', '');
$f_name = Input::Get('f_name', 'string', '');
$f_content = Input::Get('f_content', 'string', '', true);

$backLink  = "/$ADMIN/templates/";
if (!Template::IsValidPath($f_path)) {
	camp_html_goto_page($backLink);
}
$filename = Template::GetFullPath($f_path, $f_name);
$templateName = (!empty($f_path) ? $f_path."/" : "").$f_name;
if ($templateName[0] == '/') {
	$templateName = substr($templateName, 1);
}
$templateObj = new Template($templateName);

if (!file_exists($filename)) {
	camp_html_display_error(getGS("Invalid template file $1" , $f_path."/$f_name"), $backLink);
	exit;
}

if (!is_writable($filename)) {
	camp_html_add_msg(camp_get_error_message(CAMP_ERROR_WRITE_FILE, $filename));
}

$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$imageExtensions = array("png", "jpg", "jpeg", "jpe", "gif");

$templateDisplayName = $f_name;
if ($templateObj->exists()) {
	$templateDisplayName .= ' ('.getGS("Template ID:").' '.$templateObj->getTemplateId().')';
}

$crumbs = array();
$crumbs[] = array(getGS("Configure"), "");
$crumbs[] = array(getGS("Templates"), "/$ADMIN/templates/");
$crumbs = array_merge($crumbs, camp_template_path_crumbs($f_path));
$crumbs[] = array(getGS("Edit template").": $templateDisplayName", "");
echo camp_html_breadcrumbs($crumbs);

include_once($GLOBALS['g_campsiteDir']."/$ADMIN_DIR/javascript_common.php");

camp_html_display_msgs();

if (in_array($extension, $imageExtensions)) {
	$urlPath = substr($filename, strlen($Campsite['TEMPLATE_DIRECTORY']));
	?>
	<p>
	<table cellpadding="6" style="border: 1px dashed black; margin-left: 15px;">
	<tr>
		<td style="padding: 10px;">
			<img border="0" src="<?php p($Campsite['TEMPLATE_BASE_URL'].$urlPath); ?>?time=<?php p(time()); ?>">
		</td>
	</tr>
	</table>
	<p>
	<?php
} else {
	if (empty($f_content)) {
		if (is_readable($filename)) {
			$contents = file_get_contents($filename);
		} else {
			$contents = getGS("File cannot be read.");
		}
	} else {
		$contents = $f_content;
	}
	?>
	<link type="text/css" rel="stylesheet" href="<?php echo $GLOBALS['g_campsiteDir']; ?>/javascript/SyntaxHighlighter/SyntaxHighlighter.css"></link>
	<P>
	<FORM NAME="template_edit" METHOD="POST" ACTION="do_edit.php"  >
	<INPUT TYPE="HIDDEN" NAME="Path" VALUE="<?php  p($f_path); ?>">
	<INPUT TYPE="HIDDEN" NAME="Name" VALUE="<?php  p($f_name); ?>">
	<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="6" CLASS="table_input">
	<TR>
		<td align="center">
			<?php  if ($g_user->hasPermission("DeleteTempl") && is_writable($filename)) { ?>
			<INPUT TYPE="submit" class="button" NAME="Save" VALUE="<?php  putGS('Save'); ?>">
			<?php  } else { ?>
			<INPUT TYPE="button" class="button" NAME="Done" VALUE="<?php  putGS('Done'); ?>" ONCLICK="location.href='<?php echo "/$ADMIN/templates/?Path=".urlencode($f_path); ?>'">
			<?php  } ?>
		</TD>
	</TR>

	<TR>
		<TD colspan="2">
		  <TEXTAREA ROWS="40" COLS="120" NAME="cField" id="cField" WRAP="NO" class="input_text"><?php  p(htmlspecialchars($contents)); ?></TEXTAREA>
    	<script language="javascript" type="text/javascript" src="<?php echo $Campsite['WEBSITE_URL']; ?>/javascript/editarea/edit_area/edit_area_full.js"></script>
      <script language="javascript" type="text/javascript">
      editAreaLoader.init({
      	id : "cField"		          // textarea id
      	,syntax: "html"			  // syntax to be used for highgliting
      	,start_highlight: true		  // to display with highlight mode on start-up
      	,toolbar: "search, go_to_line, |, undo, redo, |, select_font, |, syntax_selection, highlight, reset_highlight, |, change_smooth_selection, fullscreen, |, help"
        ,syntax_selection_allow: "css,html,js,php,xml"
        ,replace_tab_by_spaces: 2
      });
      </script>
		</TD>
    </tr>
	</table>
    </FORM>
 	<p></p>
 	<?php
 	if ($g_user->hasPermission("DeleteTempl")
 			&& is_writable($Campsite['TEMPLATE_DIRECTORY'].$f_path)) {
 	?>
	<table class="table_input">
    <tr>
		<td align="center" colspan="2">
			<form method="POST" action="do_replace.php" onsubmit="return <?php camp_html_fvalidate(); ?>;" ENCTYPE="multipart/form-data" >
			<input type="hidden" name="f_path" value="<?php p(htmlspecialchars($f_path)); ?>">
			<input type="hidden" name="f_old_name" value="<?php p(htmlspecialchars($f_name)); ?>">
            <table >
			<tr>
				<td>
					<b><?php putGS("Replace file:"); ?></b> <input type="FILE" name="f_file" class="input_file" alt="file|<?php echo implode(",",camp_get_text_extensions()).",".implode(",", camp_get_image_extensions()); ?>" emsg="<?php putGS("You must select a file to upload."); ?>">
				</td>
				<td>
					<INPUT type="submit" name="replace" value="<?php putGS("Replace"); ?>" class="button">
				</td>
			</tr>
			</table>
            </form>
		</td>
	</TR>
	</TABLE>
	<?php } ?>
<?php } ?>
<p>
<P>

<?php camp_html_copyright_notice(); ?>
