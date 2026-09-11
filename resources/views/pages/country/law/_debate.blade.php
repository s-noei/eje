		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-title">Debate location:</div>
		<div class="law-new-content">http:// <input type="text" name="Debate" value="" size="20" maxlength="50"></div>
		<div style="clear: left; font-size: 2pt">&nbsp;</div>
		<div class="law-new-content">
			<input type="hidden" name="{{ $sub }}" value="1">
			<input type="hidden" name="token" value="{{ $form['token'] }}">
			<input type="submit" id="submits" value="Propose">
		</div>
