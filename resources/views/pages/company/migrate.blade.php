<style>
    .money { display: inline-block; width: 280px; padding: 10px; font-size: 14pt }
    .industries { display: inline-block; border: 2px #0E4A74 solid; border-radius: 5px; padding: 1px; width: 75px; font-size: 8.5pt; overflow: hidden; height: 90px; text-align: center;
    	background: -webkit-gradient(radial, 0 0, 0 bottom, from(#67BBF6), to(#1E5A84)); background: -moz-radial-gradient(center, ellipse, #67BBF6, #1E5A84); color: white }
    .industries:hover { background: -webkit-gradient(radial, 0 0, 0 bottom, from(#EAF5FF), to(#8FC9FE)); background: -moz-radial-gradient(center, ellipse, #EAF5FF, #8FC9FE); color: black }
</style>
<center><h2>Migrate the company</h2><hr size="1"></center>
You can migrate your ticket company to another type. This action is available for a limited time, only!
<br />
<div style="color: red; border-radius: 3px; border:  1px solid; padding: 3px; margin: 10px 0; text-align: center">
    <blink><b>CRITICAL CAUTION:</b> Remove all offers in local markets before doing this action. After migration, all your offers in local markets will be disappeared!</blink>
</div>
{!! $msg ?? '' !!}
		<form action="" method="post" name="migrate">
			@csrf
            <table>
                <tr>
                    <td>Migrate the company to:</td>
                    <td>
@foreach ($industries as $ind)
    						<div class="industries" id="{{ $ind['IndustryID'] }}">
                                <label class="extrawrk" title="{{ $lang->getstr('industry_' . strtolower($ind['iName'])) }}">
                                    <img src="/images/icons/{{ $ind['Icon'] }}.png" /><br />
                                    {!! $lang->getstr('industry_' . strtolower($ind['iName'])) !!}<br />
                                    <input type="radio" name="iName" value="{{ $ind['IndustryID'] }}" />
                                </label>
                            </div>
@endforeach
                    </td>
                </tr>
                <tr><td colspan="2" style="text-align: center"><input type="submit" name="submigrate" value="Migrate!" class="submit-blue-1" onclick="return confirm('Are you sure?')" /></td></tr>
            </table>
        </form>
