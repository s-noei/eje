@if (count($polls))
			<div style="border: 1px solid; -moz-border-radius: 5px; -webkit-border-radius: 5px; padding: 3px"><form action="" method="post">
			@csrf
@php $anyOpen = false; @endphp
@foreach ($polls as $i => $poll)
@php $anyOpen = $anyOpen || $poll['canAnswer']; @endphp
		<b>{{ $i + 1 }}.</b> {{ $poll['q']['q'] }}
		<center><div style="display: block; width: 400px; border: 1px #666 solid; text-align: justify; padding: 1px">
@if ($poll['canAnswer'])
@foreach ($poll['options'] as $ans)
						<label><input type="radio" name="s[{{ $poll['q']['qID'] }}]" value="{{ $ans['sID'] }}"> {{ $ans['title'] }}</label><br>
@endforeach
@else
					<table style="width: 398px">
@foreach ($poll['results'] as $ans)
							<tr>
								<th style="width: 320px;"><b>{{ $ans['title'] }}</b></th>
								<td style="width: 28px">{{ $ans['Votes'] }}</td>
								<td style="width: 50px">{{ $ans['pers'] }}%</td>
							</tr>
@endforeach
					</table>
@endif
				</div></center>
				<hr size="1">
@endforeach
@if ($anyOpen)
					<center>
						<input type="submit" name="subpoll" value="Submit my opinion" class="submit-blue-1">
						<input type="reset" value="Reset form" class="submit-blue-1">
					</center>
@endif
			</form></div>
@endif
