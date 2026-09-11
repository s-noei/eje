@extends('layouts.game')
@section('content')
<h3>Create company</h3>
<div width="90%">
{!! $msg ?? '' !!}
<style>
    .money { display: inline-block; width: 280px; padding: 10px; font-size: 14pt }
    .industries { display: inline-block; border: 2px #0E4A74 solid; border-radius: 5px; padding: 1px; width: 64px; font-size: 8.5pt; overflow: hidden; height: 90px; text-align: center;
    	background: -webkit-gradient(radial, 0 0, 0 bottom, from(#67BBF6), to(#1E5A84)); background: -moz-radial-gradient(center, ellipse, #67BBF6, #1E5A84); color: white }
    .industries:hover { background: -webkit-gradient(radial, 0 0, 0 bottom, from(#EAF5FF), to(#8FC9FE)); background: -moz-radial-gradient(center, ellipse, #EAF5FF, #8FC9FE); color: black }
</style>
<center><hr size="1"></center>
		<div class="money">Cost: {{ $price }} <img src="/images/tala.gif" alt="Tala" align="absmiddle"></div>
		<div class="money">You have: {{ $money }} <img src="/images/tala.gif" alt="Tala" align="absmiddle"></div>
        <hr size="1">
		<form action="" method="post" name="create">
			@csrf
            <table>
                <tr><td>Name of company:</td><td><input type="text" name="cName" size="30"></td></tr>
                <tr>
                    <td>Industry:</td>
                    <td>
@foreach ($industries as $ind)
    						<div class="industries" id="{{ $ind['IndustryID'] }}">
                                <label class="extrawrk" title="{{ $ind['iName'] }}">
                                    <img src="/images/icons/{{ $ind['Icon'] }}.png" /><br />
                                    {!! $lang->getstr('industry_' . strtolower($ind['iName'])) !!}<br />
                                    <input type="radio" id="ind{{ $ind['IndustryID'] }}" name="iName" value="{{ $ind['IndustryID'] }}" style="visibility: hidden;" />
                                </label>
                            </div>
@endforeach
                    </td>
                </tr>
                <tr><td>&nbsp;</td><td><input type="hidden" name="do" value="Company"><input type="submit" name="Create" value="Create" class="submit-blue-1"></td></tr>
            </table>
		</form>
        <script type="text/javascript">
            var actOpt = '';
            $(document).ready(function(){
                $(".industries").click(function(){
                    var cID = $(this).attr("id");
                    document.getElementById('ind'+cID).checked = true;
                    if (actOpt) $("div#"+actOpt).css("border", "2px #0E4A74 solid");
                    $("div#"+cID).css("border", "2px solid #CC3333");
                    actOpt = cID;
                });
            });
        </script>
</div>
@endsection
