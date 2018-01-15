function t_initZoom(){if($('[data-zoomable="yes"]').length){$('[data-zoomable="yes"]').addClass("t-zoomable");$("body").append('<div class="t-zoomer__wrapper">\
      <div class="t-zoomer__container">\
      </div>\
      <div class="t-zoomer__bg"></div>\
      <div class="t-zoomer__close">\
        <div class="t-zoomer__close-line t-zoomer__close-line-first"></div>\
        <div class="t-zoomer__close-line t-zoomer__close-line-second"></div>\
      </div>\
    </div>');t_showZoom();$('.t-zoomer__close, .t-zoomer__bg').click(function(e){$('body').removeClass("t-zoomer__show");$('body').removeClass("t-zoomer__show_fixed");$(document).unbind('keydown')})}}
function t_showZoom(){$('.t-zoomable').click(function(e){$("body").addClass("t-zoomer__show");$(".t-zoomer__container").html('<div class="t-carousel__zoomed">\
      <div class="t-carousel__zoomer__slides">\
        <div class="t-carousel__zoomer__inner">\
        </div>\
        <div class="t-carousel__zoomer__control t-carousel__zoomer__control_left" data-zoomer-slide="prev">\
          <div class="t-carousel__zoomer__arrow__wrapper t-carousel__zoomer__arrow__wrapper_left">\
            <div class="t-carousel__zoomer__arrow t-carousel__zoomer__arrow_left t-carousel__zoomer__arrow_small"></div>\
          </div>\
        </div>\
        <div class="t-carousel__zoomer__control t-carousel__zoomer__control_right" data-zoomer-slide="next">\
          <div class="t-carousel__zoomer__arrow__wrapper t-carousel__zoomer__arrow__wrapper_right">\
            <div class="t-carousel__zoomer__arrow t-carousel__zoomer__arrow_right t-carousel__zoomer__arrow_small"></div>\
          </div>\
        </div>\
      </div>\
    </div>');var id=$(this).closest(".r").attr("id"),images=$("#"+id+"").find(".t-zoomable");if($("#"+id+"").find(".t-slds").length){var slider=$(this).closest(".t-slds");images=slider.find(".t-zoomable")}
images.each(function(){var images_urls=$(this).attr('data-img-zoom-url').split(',');if($(this).is("img")){var imgtitle=$(this).attr('title');var imgdescr=$(this).attr('data-img-zoom-descr')}
if($(this).is("div")){var imgtitle=$(this).attr('title');var imgdescr=$(this).attr('data-img-zoom-descr')}
if(typeof imgtitle!=="undefined"&&imgtitle!==!1){var titlebody="<div class=\"t-zoomer__title t-name t-descr_xxs\">"+imgtitle+"</div>"}else{var titlebody=""}
if(typeof imgdescr!=="undefined"&&imgdescr!==!1){var descrbody="<div class=\"t-zoomer__descr t-descr t-descr_xxs\">"+imgdescr+"</div>"}else{var descrbody=""}
$(".t-carousel__zoomer__inner").append("<div class=\"t-carousel__zoomer__item\"><div class=\"t-carousel__zoomer__wrapper\"><img class=\"t-carousel__zoomer__img\" src=\""+images_urls+"\"></div><div class=\"t-zoomer__comments\">"+titlebody+descrbody+"</div></div>")});var image_descr=$(".t-carousel__zoomer__item");image_descr.each(function(){$(this).css("display","block");var zoomercomments=$(this).find(".t-zoomer__comments");var zoomertitle=zoomercomments.find('.t-zoomer__title');var zoomerdescr=zoomercomments.find('.t-zoomer__descr');if(!zoomertitle.length&&!zoomerdescr.length){zoomercomments.css('padding','0')}
var height=zoomercomments.innerHeight();$(this).css("display","");var image_active=$(this).find(".t-carousel__zoomer__wrapper");image_active.css("bottom",height)});var target_url=$(this).attr("data-img-zoom-url"),target_img=$(".t-carousel__zoomer__img[src=\""+target_url+"\"]"),slideItem=$(".t-carousel__zoomer__item"),target=target_img.closest(slideItem);target.show(0);slideItem.each(function(){$(this).attr("data-zoomer-slide-number",$(this).index())});pos=parseFloat($(".t-carousel__zoomer__item:visible").attr("data-zoomer-slide-number"));$('.t-carousel__zoomer__control_right').click(function(){pos=(pos+1)%slideItem.length;slideItem.hide(0).eq(pos).show(0)});$('.t-carousel__zoomer__control_left').click(function(){pos=(pos-1)%slideItem.length;slideItem.hide(0).eq(pos).show(0)});$(document).keydown(function(e){if(e.keyCode==37){pos=(pos-1)%slideItem.length;slideItem.hide(0).eq(pos).show(0)}
if(e.keyCode==39){pos=(pos+1)%slideItem.length;slideItem.hide(0).eq(pos).show(0)}
if(e.keyCode==27){$('body').removeClass("t-zoomer__show");$('body').removeClass("t-zoomer__show_fixed");$(document).unbind('keydown')}});var defaultSwipe;$(".t-carousel__zoomer__inner").bind('touchstart',function(e){defaultSwipe=e.originalEvent.touches[0].clientX});$(".t-carousel__zoomer__inner").bind('touchend',function(e){var swiped=e.originalEvent.changedTouches[0].clientX;if(defaultSwipe>swiped+50){pos=(pos-1)%slideItem.length;slideItem.hide(0).eq(pos).show(0)}else if(defaultSwipe<swiped-50){pos=(pos+1)%slideItem.length;slideItem.hide(0).eq(pos).show(0)}});var slides_count=$(".t-carousel__zoomer__item").size();if(slides_count>1){$('body').addClass("t-zoomer__show_fixed")}else{$(".t-carousel__zoomer__control").css("display","none")}
$('.t-carousel__zoomer__inner').click(function(e){$('body').removeClass("t-zoomer__show");$('body').removeClass("t-zoomer__show_fixed");$(document).unbind('keydown')});var lastScrollTop=0;$(window).scroll(function(event){var st=$(this).scrollTop();if(st>lastScrollTop){$('body').not('.t-zoomer__show_fixed').removeClass("t-zoomer__show");$(document).unbind('keydown')}
lastScrollTop=st})})}
$(document).ready(function(){t_initZoom()})