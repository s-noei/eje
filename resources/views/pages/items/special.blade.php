@extends('layouts.game')
@section('content')
<h3> Special Items </h3>
{!! $msg ?? '' !!}
<link rel="stylesheet" type="text/css" href="/include/css/special_items.css">
<div class="ItemContainerRow">
@foreach ([[1, 'Health Kit <br>(5 Stars)', 5, 'Gold Account', 'Recover 100 wellness.<br>', '/images/icons/health-kit.png', 'FF0000', 'Buy', 'Item'],
           [2, 'Gold Pack <br>(30 Days)', 50, 'none', 'Enables all game functions for 30 days.', '/images/icons/gold-pack.png', '8847FF', 'Buy', 'Item'],
           [3, 'Damage Booster (+20%)', 2, 'Gold Account', '+20% Damage for 20 minutes.', '/images/icons/damge-booster.png', 'FF0000', 'BuyUse', 'ItemUse'],
           [4, 'Damage Booster (+50%)', 5, 'Gold Account', '+50% Damage for 20 minutes.', '/images/icons/damge-booster.png', '8847FF', 'BuyUse', 'ItemUse']] as [$id, $name, $price, $req, $info, $img, $bg, $btn, $field])
	<form action="" method="post" name="{{ $btn }}">
	@csrf
	<input type="hidden" name="{{ $field }}" value="{{ $id }}">
	<div class="Item">
		<div class="ItemContainerCell ItemContainerCellLarge ItemDetailsMiniPopupBorderInactive" style="border-color: #{{ $bg }};">
			<div class="ItemImage NoSelect ItemImageLarge"><img src="{{ $img }}" style="opacity: 1;"></div>
			<div class="ItemText"><div class="Name"> {!! $name !!} </div>
			<div class="Rarity" style="color: #FF3333;"> Requirements: </div>
			<div class="Rarity" style="color: rgb(104, 104, 104);"> {{ $req }} </div>
    		<span class="Price"> Description: <br> <strong> {!! $info !!} </strong> <br> Cost: <br> <strong>{{ $price }}</strong> <img src="/images/tala.gif" alt="Tala" align="absmiddle"> Tala </span>
    		<div class="NoSelect GenericGreenButton HoverAddToCartButton" style="display: block;">
    		<div class="LeftCap"></div>
    		<div class="RightCap"></div>
    		<span><input type="submit" name="{{ $btn }}" value="{{ $btn === 'Buy' ? 'Buy' : 'Use' }}" class="BuyItems"></span>
    		</div>
    		</div>
    	</div>
	</div>
	</form>
@endforeach
</div>
@endsection
