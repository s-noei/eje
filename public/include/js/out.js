    var actView = 0;
    function changeView()
    {
        oldView = actView;
        actView++;
        if (actView > 4) actView = 1;
        if (oldView) {
                $("#feature-"+oldView+" img").animate({width: "32px", height: "32px"}, 300, function(){
                    $("#feature-"+oldView).animate({opacity: "0.3", margin: "100px 0px 0px "+(10 + (oldView * 35))+"px", width: "32px", height: "32px"}, 1000)
                });
                $(".features-desc #desc-"+oldView).fadeOut(300, function(){
                    $(".features-title #title-"+oldView).fadeOut(300);
                });
            }
        $("#feature-"+actView).animate({opacity: "1", margin: "32px 0px 0px 30px", width: "64px", height: "64px"}, 1000, function(){
            $("#feature-"+actView+" img").animate({width: "64px", height: "64px"}, 300, function(){
                $(".features-title #title-"+actView).fadeIn(1000, function(){
                    $(".features-desc #desc-"+actView).fadeIn(1000);
                });
            });
        });
        
    }
    $(document).ready(function(){
        changeView();
        setInterval("changeView();",10000);
    });