<script type="text/javascript" src="/include/js/editor/tiny_mce_gzip.js"></script>
<script type="text/javascript">
if (window.tinyMCE_GZ) tinyMCE_GZ.init({
	plugins : 'style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras',
	themes : 'simple,advanced', languages : 'en', disk_cache : true, debug : false
});
</script>
<script type="text/javascript">
if (window.tinyMCE) tinyMCE.init({
theme : "advanced", mode : "textareas", plugins : "directionality,preview,advimage,save",
theme_advanced_layout_manager : "SimpleLayout",
theme_advanced_buttons1 : "|,save,cancel,preview,|,bold,italic,underline,|,cut,copy,paste,|,sup,sub,blockquote,|,rtl,ltr,|,link,unlink,image,|",
theme_advanced_buttons2 : "", theme_advanced_buttons3 : "",
theme_advanced_toolbar_location : "top", theme_advanced_toolbar_align : "left", theme_advanced_statusbar_location : "bottom",
theme_advanced_resizing : true, theme_advanced_resizing_use_cookie : false, width: "150", plugin_preview_width : "500", plugin_preview_height : "400"
});
</script>
<blockquote>
	<h3>Change country's welcome message</h3>
	<form action="" method="post">
		@csrf
		<div class="law-new-title">New welcome message:</div>
		<div class="law-new-content"><textarea name="newmsg"></textarea></div>
		@include('pages.country.law._debate', ['sub' => 'subwelmsg'])
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">Current welcome message:</div>
		<div class="law-new-content" style="width: 330px; font-size: 9pt">{!! nl2br(e($row['welcome_message'])) !!}</div>
	</form>
</blockquote>
