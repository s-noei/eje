$(document).ready(function(){
		$('#data').click(function(){
				$(activeBox).fadeOut(200, function(){
						$('#box-data').fadeIn(200)
						activeBox = '#box-data'
					})
			})

		$('#career').click(function(){
				$(activeBox).fadeOut(200, function(){
						$('#box-career').fadeIn(200)
						activeBox = '#box-career';
					})
			})

		$('#bio').click(function(){
				$(activeBox).fadeOut(200, function(){
						$('#box-bio').fadeIn(200)
						activeBox = '#box-bio';
					})
			})
	});