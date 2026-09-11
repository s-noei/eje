<script type="text/javascript" src="/include/js/editor/tiny_mce_gzip.js"></script>
<script type="text/javascript">
if (window.tinyMCE_GZ) tinyMCE_GZ.init({
	plugins : 'style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras',
	themes : 'simple,advanced', languages : 'en', disk_cache : true, debug : false
});
if (window.tinyMCE) tinyMCE.init({
theme : "advanced", mode : "textareas", plugins : "directionality,preview,advimage,save",
theme_advanced_layout_manager : "SimpleLayout",
theme_advanced_buttons1 : "|,save,cancel,preview,|,bold,italic,underline,|,cut,copy,paste,|,sup,sub,blockquote,|,rtl,ltr,|,link,unlink,image,|",
theme_advanced_buttons2 : "", theme_advanced_buttons3 : "",
theme_advanced_toolbar_location : "top", theme_advanced_toolbar_align : "left", theme_advanced_statusbar_location : "bottom",
theme_advanced_resizing : true, plugin_preview_width : "500", plugin_preview_height : "400"
});
</script>
<form action="{{ $vars->getURL('mail', 'compose') }}" method=post>
@csrf
<input type=hidden name=toID value="{{ $toID }}">
<input type=hidden name=toDo value="compose">
<table border="0" width="80%" id="table1">
	<tr>
		<td width="104" class=tdstyle>{{ $lang->getstr('pm_to', 'pm') }}:</td>
		<td class=tdstyle>
@if (!$replyTo)
			<input type="text" name="toName" value="{{ $toName }}" size="50">
@else
			{{ $toName }}<input type=hidden name=toName value='{{ $toName }}'>
@endif
		</td>
	</tr>
	<tr>
		<td width="104" class=tdstyle>{{ $lang->getstr('pm_subject', 'pm') }}:</td>
		<td class=tdstyle><input type="text" name="subject" value="{{ $rSubject ? 'Re: ' . $rSubject : '' }}" size="50"></td>
	</tr>
	<tr>
		<td width="104" class=tdstyle valign=top>{{ $lang->getstr('pm_body', 'pm') }}:</td>
		<td class=tdstyle><textarea rows="8" name="body" cols="50">{{ $rBody ? '<blockquote><b>' . $toName . ' wrote:</b><br>' . $rBody . '</blockquote><br>' : '' }}</textarea></td>
	</tr>
	<tr>
		<td colspan="2" class=tdstyle>
		<input type="submit" id=buttons value="{{ $lang->getstr('pm_send', 'pm') }}" name="B1">
@if (!$replyTo)
		<p>{{ $lang->getstr('pm_admin', 'pm') }}
@endif
		</td>
	</tr>
</table>
</form>
