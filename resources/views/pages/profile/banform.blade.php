@php $pun = (int) ($citInfo['at_punishment'] ?? 0); @endphp
			<div class="column-double" style="text-align: center; background: white">
				<div id="banuser" style="text-align: justify; display: none">
					<hr size="2">
					<form name="violation" action="" method="POST">
						@csrf
						<input type="radio" name="ftype" value="predef" id="opredef"> Add a pre-defined violation
						<br>
						<div id="fpredef" style="display: none; text-align: center">
							<select name="predef">
								<option value="1">Spamming</option>
								<option value="2">Insult</option>
								<option value="3">Illegal downvote</option>
								<option value="4">Public accusation</option>
								<option value="5">Try to trade Tala</option>
								<option value="6">Warning</option>
							</select>
							<br>
							Description:<br>
							<textarea name="predesc" cols="30" rows="3"></textarea><br>
						</div>
@if ($pun >= 2)
						<input type="radio" name="ftype" value="custom" id="ocustom"> Add a custom violation
						<br>
						<div id="fcustom" style="display: none; margin: 0 20px 0 20px">
							Title:<br><input type="text" name="vtitle"><br>
							Description:<br><textarea name="vdesc" cols="30" rows="3"></textarea><br>
							Points:<br><input type="text" name="vpoints">
						</div>
@endif
@if ($pun >= 3)
						<input type="radio" name="ftype" value="arrest" id="ojail"> Arrest this citizen
						<br>
						<div id="fjail" style="display: none; margin: 0 20px 0 20px">
							Reason:<br>
							<input type="radio" name="jailreason" value="administrating non-political multiple accounts"> Administrating non-political multiple accounts<br>
							<input type="radio" name="jailreason" value="administrating political multiple accounts"> Administrating political multiple accounts<br>
							<input type="radio" name="jailreason" value="using bugs"> Using bug<br>
							<input type="radio" name="jailreason" value="having a successful trade of Tala"> Having a successful trade of Tala<br>
							<input type="radio" name="jailreason" value="other"> Other<br>
							<br>
							<textarea name="jaildesc" cols="30" rows="6"></textarea><br>
							Duration
							<select name="jaildur">
								<option value="1">a day</option>
								<option value="2">2 days</option>
								<option value="7">a week</option>
								<option value="15">15 days</option>
								<option value="30">a month</option>
							</select>
						</div>
@endif
@if ($pun >= 4)
						<input type="radio" name="ftype" value="ban" id="oban"> Ban this citizen
						<br>
						<div id="fban" style="display: none; margin: 0 20px 0 20px">
							Ban reason:<br>
							<input type="radio" name="banreason" value="using bugs/exploits"> Using bugs/exploits<br>
							<input type="radio" name="banreason" value="having multiple accounts"> Having multiple accounts<br>
							<input type="radio" name="banreason" value="properties obtained through an illegal or unjust method"> Illegal methods for properties<br>
							<input type="radio" name="banreason" value="other"> Other<br>
							<textarea name="bandesc" cols="30" rows="6"></textarea><br>
						</div>
@endif
						<input type="hidden" name="addviolation" value="1">
						<input type="submit" value="Submit violation" id="submits">
					</form>
					<script>
						$(document).ready(function(){
								$("#opredef").click(function(){ $("#fcustom, #fban, #fjail").hide(); $("#fpredef").fadeIn(500) });
								$("#ocustom").click(function(){ $("#fban, #fpredef, #fjail").hide(); $("#fcustom").fadeIn(500) });
								$("#oban").click(function(){ $("#fcustom, #fpredef, #fjail").hide(); $("#fban").fadeIn(500) });
								$("#ojail").click(function(){ $("#fcustom, #fpredef, #fban").hide(); $("#fjail").fadeIn(500) });
							});
					</script>
				</div>
			</div>
