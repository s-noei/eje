@extends('layouts.game')
@section('content')
@include('pages.media._np-head')
<style>
	.comment-votes { display: inline-block; vertical-align: top }
</style>
@php $me = $citInfo['CitizenID'] ?? 0; $aID = $art['aID']; @endphp
<script language="javascript">
	function vote(vType, reason)
	{
		loadAjax('/include/ajFunc.php?q=vote&p={{ $aID }}&u={{ $me }}&v='+vType+'&r='+reason+'&token={{ $voteToken }}', 'points');
		return false;
	}
	function setCMVote(cmID, vote, token)
	{
		var target = (vote == 1) ? 'cm-'+cmID+'-up' : 'cm-'+cmID+'-down';
		loadAjax('/include/ajFunc.php?q=cmvote&p='+cmID+'&u={{ $me }}&v='+vote+'&token='+token, target);
		return false;
	}
	function addQuote(cmID)
	{
		$(".quotes a").text("Quote");
		$(".quotes a").removeClass("button-red-0").addClass("button-blue-0");
		if (document.addComment.quote.value != cmID) {
			document.addComment.quote.value = cmID;
			$("a#quote-"+cmID).text("Unquote");
			$("a#quote-"+cmID).removeClass("button-blue-0").addClass("button-red-0");
		}else
			document.addComment.quote.value = '';
	}
	function addSmiley(what, wher)
	{
		document.getElementById('cm-body').value += ':' + what + ':';
		document.getElementById('cm-body').focus();
		return false;
	}
	$(document).ready(function(){
		$("#voters_link").click(function(){ $('#voters_list').slideToggle('fast'); });
		$("#agree").click(function(){
				$(".votebut").fadeOut("fast", function(){
						$(".points").css("padding-top","16px"); $(".points").css("height","44px"); vote('1');
				});
			});
		$("#disagree").click(function(){
				$("#Vote-reason").fadeIn("fast");
				$(".votebut").fadeOut("fast", function(){ $(".points").css("padding-top","16px"); $(".points").css("height","44px"); });
			});
		$("#spam").click(function(){ $("#Vote-reason").fadeOut("fast", function(){ vote(-1, 'spam'); }); });
		$("#insult").click(function(){ $("#Vote-reason").fadeOut("fast", function(){ vote(-1, 'insult'); }); });
		$("#other").click(function(){ $("#Vote-reason").fadeOut("fast", function(){ vote(-1, 'other'); }); });
		$("a#wantmore").click(function(){ $("#moresmileys").slideToggle(200); });
	});
</script>
<div class="column-double">
@if ($art['Deleted'])
				<h3 class="errHandle">This article is removed by <a href="{{ $vars->getURL('profile', $art['removerID']) }}">{{ $art['removerName'] }}</a>.</h3>
@endif
@if (!$art['Deleted'] || $canMod)
			<center>
@if ($art['mastprove'])
						<img src="/images/game/mastprove.gif"><br>
@endif
				<font size="5">{{ strip_tags($art['aTitle']) }}</font>
			</center>
</div>
<div class="column-double">
			<div id="article_operations">
				<div id="points" class="points"{!! $isVoted ? ' style="padding-top: 16px; height: 44px"' : '' !!}>
@if (!$isVoted)
					<div id="Vote-agree" class="votebut"><a class="voteit" id="agree">+</a></div>
@endif
					{{ $art['aVotes'] }}
@if (!$isVoted && $logged && $citInfo['nationality'] == $art['aCountry'])
					<div id="Vote-disagree" class="votebut"><a class="voteit" id="disagree">-</a></div>
					<div id="Vote-reason" class="info-box" style="width: 40px; color: black">
						{{ $lang->getstr('np_vote_why', 'np') }}<br>
						<a class="cmdRemove" href="javascript:void(0)" id="spam">{{ $lang->getstr('np_vote_spam', 'np') }}</a><br>
						<a class="cmdRemove" href="javascript:void(0)" id="insult">{{ $lang->getstr('np_vote_insult', 'np') }}</a><br>
						<a class="cmdRemove" href="javascript:void(0)" id="other">{{ $lang->getstr('np_vote_other', 'np') }}</a><br>
					</div>
@endif
				</div>
				<a id="voters_link" href="javascript:void(0)">{{ $lang->getstr('np_voters', 'np') }}</a>
				<div id="voters_list" class="info-box" style="width: 300px;">
					{{ $lang->getstr('np_voters_reasons', 'np') }}:<br>
@foreach ($reasons as $reason)
					<b>{{ $lang->getstr('np_vote_' . strtolower($reason['reason']), 'np') }}:</b> {{ $reason['Votes'] }}
@endforeach
					<hr>
					{{ $lang->getstr('np_voters_list', 'np') }}:<br>
@foreach ($voters as $voter)
					<a href="{{ $vars->getURL('profile', $voter['CitizenID']) }}" style="color: {{ $voter['vote'] == 1 ? 'blue' : 'red' }}">{{ $voter['name'] }}&nbsp;</a>
@endforeach
				</div>
@if (($isAuthor || $canMod) && !$art['Deleted'])
					<form name="artOper" action="" method="post">
					@csrf
					<input type="hidden" name="actArt" value="{{ $aID }}">
					<a href="{{ $vars->getURL('article', $aID, 'edit') }}" class="cmdEdit">{{ $lang->getstr('np_article_edit', 'np') }}</a><br>
					<input type="submit" class="cmdRemove" name="artRemove" value="{{ $lang->getstr('np_article_remove', 'np') }}" onclick="return confirm('{{ $lang->getstr('confirm_remove_article', 'msgs') }}')">
@if ($session->isAdmin() && !$art['mastprove'])
							<br><input type="submit" class="cmdEdit" name="artAppr" value="Appr.">
@endif
					</form>
@endif
			</div>
			<hr size="1">
				<sub>
					<img src="/images/flags/s/{{ $art['aFlag'] }}.gif" class="flag-xs" align="absmiddle">
					| {{ sprintf($lang->getstr('wrote'), date("H:i:s", $art['timestamp']), $database->getToday($art['timestamp'])) }}
					| {!! $session->getDiff($art['timestamp']) !!}
				</sub>
			<hr size="1">
			<div id="article_contents"{!! $art['RTL'] ? ' style="direction: rtl"' : '' !!}>
@include('pages.media._poll')
@if ($art['aPic'])
					<img src="{{ $art['aPic'] }}" alt="eJahan" class="artPic">
@endif
@if ($art['aSound'])
					<audio controls autoplay src="{{ $art['aSound'] }}"></audio>
@endif
@if ($art['aPic'] || $art['aSound'])
					<br>
@endif
				{!! nl2br(stripslashes($vars->utfcorrect($art['aContent']))) !!}
			</div>
@if ($showFb)
					<iframe src="http://www.facebook.com/plugins/like.php?href=http://www.ejahan.com{{ request()->getRequestUri() }}&layout=standard&show_faces=false&width=650&action=like&colorscheme=light" scrolling="no" frameborder="0" allowTransparency="true" style="border:none; overflow:hidden; width:650px; height:25px"></iframe>
@endif
			<div style="clear: both">&nbsp;</div>
</div>
<hr>
@if (count($comments) > 0)
<div class="column-double">
		<center>{{ sprintf($lang->getstr('np_comment_count', 'np'), count($comments)) }}</center>
</div>
<hr>
@foreach ($comments as $cm)
			<div id="comments">
				<div id="comment-head">
						<center>
							<a href="{{ $vars->getURL('profile', $cm['CitID']) }}">
								{!! $vars->getAvatar($cm, 'Avatar-xs') !!}
								<br>
								{{ $cm['name'] }}
							</a>
							<br>
							{!! $session->getDiff($cm['timestamp']) !!}
							<br>
							<div class="comment-votes-holder" id="cm-{{ $cm['cmID'] }}">
								<div class="comment-votes">
@if (!$cm['Voted'] && $logged)
									<a href="javascript:void(0)" onclick="javascript:setCMVote({{ $cm['cmID'] }}, 1, '{{ md5($cm['cmID'] . $me . '1k3y4 v0+lnj C0MM3NT') }}')">
@endif
										<img src="/images/game/thumbs_up.png" width="24px" align="absmiddle">
@if (!$cm['Voted'] && $logged)
									</a>
@endif
									<span id="cm-{{ $cm['cmID'] }}-up" style="color: green; width: 20px; display: block">{{ $cm['thumbs_up'] }}</span>
								</div>
								<div class="comment-votes">
@if (!$cm['Voted'] && $logged)
									<a href="javascript:void(0)" onclick="javascript:setCMVote({{ $cm['cmID'] }}, -1, '{{ md5($cm['cmID'] . $me . '-1k3y4 v0+lnj C0MM3NT') }}')">
@endif
										<img src="/images/game/thumbs_down.png" width="24px" align="absmiddle">
@if (!$cm['Voted'] && $logged)
									</a>
@endif
									<span id="cm-{{ $cm['cmID'] }}-down" style="color: red; width: 20px; display: block">{{ $cm['thumbs_down'] }}</span>
								</div>
							</div>
@if ($logged && ($canMod || $cm['CitID'] == $me))
@if ($cm['Deleted'])
							<font color='#a82200'><b>DELETED COMMENT!</b></font>
@else
								<form name="CMOper-{{ $cm['cmID'] }}" action="" method="post">
								@csrf
								<input type="hidden" name="actCM" value="{{ $cm['cmID'] }}">
								<input type="submit" class="submit-red-0" name="removeCM" value="{{ $lang->getstr('np_article_remove', 'np') }}" onclick="return confirm('Are you sure?')">
								</form>
@endif
@else
							<div class="quotes">
								<a href="javascript:void(0)" onclick="javascript:addQuote({{ $cm['cmID'] }})" class="button-blue-0" id="quote-{{ $cm['cmID'] }}">Quote</a>
							</div>
@endif
					</center>
				</div>
				<div id="comment-body">
@if ($cm['quote'])
						<blockquote>
							<i style="border-bottom: 1px solid"><b>Quoted this comment by <a href="{{ $vars->getURL('profile', $cm['quoted_id']) }}">{{ $cm['quoted_name'] }}</a></b></i>
							<br>
							{!! $cm['quoted_body'] !!}
						</blockquote>
@endif
					{!! nl2br($session->addSmileys($cm['cmBody'])) !!}
				</div>
				<div style="clear: both;"></div>
			</div>
			<div id="comments"><hr size="1" width="490px"></div>
@endforeach
@endif
@if ($logged)
@php
	$smileys1 = ['angry','devil','grin','happy','happy2','huh','love','sad','sunglasses','tongue','uncertain','wink'];
	$smileys2 = ['ask','blush','cigar','cowboy','cry','devil2','devil3','eh','exclamation','glasses','graduated','guilty','hmm','jealous','king','lol','ohno','ohno2','party','puke','rambo','ready','roll','shades','skull','sleepy','steal','stun','thdown','thup','tired','wacs','weird','whistle','worried','wubs'];
@endphp
			<div id=comments class=post-comment>
				<a name="comment"></a>
				<div style="text-align: center"><h2>{{ $lang->getstr('np_comment_post', 'np') }}!</h2></div>
				<div class="smileys">
					<u>{{ $lang->getstr('np_comment_smiley', 'np') }}</u><br>
					<table align="center">
@foreach (array_chunk($smileys1, 4) as $chunk)
						<tr>
@foreach ($chunk as $s)
							<td><a href="#" onclick="return addSmiley('{{ $s }}', 'cm-body')"><img src="/images/smileys/{{ $s }}.gif" class="smiley"></a></td>
@endforeach
						</tr>
@endforeach
					</table>
					<a href="javascript:void(0)" id="wantmore">More smileys</a>
				<div id="moresmileys" style="display: none; background: white;">
					<table align="center">
@foreach (array_chunk($smileys2, 4) as $chunk)
						<tr>
@foreach ($chunk as $s)
							<td><a href="#" onclick="return addSmiley('{{ $s }}', 'cm-body')"><img src="/images/smileys/{{ $s }}.gif" class="smiley"></a></td>
@endforeach
						</tr>
@endforeach
					</table>
				</div>
				</div>
				<div class="comment-area">
					<form action="#comment" name="addComment" method="post" class="addCM">
						@csrf
						<textarea rows="8" id="cm-body" name="cm-body" cols="45" class="cm-textarea"></textarea><br><br>
						<input type="hidden" name="quote" value="">
						<input type="hidden" name="go" value="post-cm">
						<input type=submit value="{{ $lang->getstr('np_comment_post', 'np') }}!" id=submits>
					</form>
				</div>
			</div>
@endif
@endif
@endsection
