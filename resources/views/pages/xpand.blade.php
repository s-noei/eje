<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>eJahan Xpand BETA</title>
	<style>
		.xpand-table { width: 100% }
		.xpand-table td { text-align: center; font-family: Tahoma; font-size: 9pt; padding: 3px }
	</style>
</head>
<body>
	<blockquote style="text-align: center">
		<img src="/xpand/logo.gif">
		<hr>
		<table class="xpand-table" border="1" bordercolor="black">
			<tr><td>Welcome to eJahan Xpand!</td></tr>
			<tr><td>eJahan Xpand is an API system to make optional websites which help eJahan players.</td></tr>
			<tr>
				<td>
					<b>Current available API engines</b>
					<blockquote>
						<hr>
						<b>Region API</b><br>
						<blockquote>
							<b>JSON:</b> {{ url('/xpand/region-{ID}.html') }}<br>
							<b>XML:</b> {{ url('/xpand/region-{ID}.xml') }}
						</blockquote>
						<b>Citizen API</b><br>
						<blockquote>
							<b>JSON:</b> {{ url('/xpand/citizen-{NAME}.html') }}<br>
							<b>XML:</b> {{ url('/xpand/citizen-{ID}.xml') }}
						</blockquote>
					</blockquote>
				</td>
			</tr>
			<tr>
				<td style="text-align: justify">
					<b>May 9th, 2010:</b> eJahan xPand is started officially.
					<hr>
					<b>Apr. 10th, 2010:</b> The base system released.
				</td>
			</tr>
		</table>
	</blockquote>
</body>
</html>
