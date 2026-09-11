<script type="text/javascript" src="/include/js/editor/tiny_mce.js"></script>
<script type="text/javascript">
	if (window.tinyMCE) tinyMCE.init({
			theme : "advanced", mode : "textareas", plugins : "directionality,preview,advimage,save",
			theme_advanced_layout_manager : "SimpleLayout",
			theme_advanced_buttons1 : "save,cancel,preview,|,bold,italic,underline,|,cut,copy,paste,|,sup,sub,blockquote,hr,bullist,numlist,|,link,unlink,image",
			theme_advanced_buttons2 : "fontselect,fontsizeselect,|,redo,undo,|,forecolor", theme_advanced_buttons3 : "",
			theme_advanced_toolbar_location : "top", theme_advanced_toolbar_align : "center", theme_advanced_statusbar_location : "bottom",
			theme_advanced_font_sizes : "8px,9px,10px,12px,14px,16px,24px", width: "{{ $w ?? 650 }}", height: "400", plugin_preview_width : "500", plugin_preview_height : "400"
		});
</script>
