$(document).ready(function(){
	$('#tnb .tnb_shop').parent('li').css('display','none');
	$('#tnb .tnb_service').parent('li').css('display','none');
	$('#tnb .tnb_mb_area').parent('li').css('width','auto');
	$('#gnb .on .gnb_oparea').css('display','block');
	$('#logo > a >img').attr('src','/theme/basic/img/main/logo.png');

	var tit_name = $('#container_title').text();
	$('.container_wr').prepend('<h1 class="contain_tit">'+tit_name+'</h1>');


	$('#gnb .gnb_li button').on('click',function(){
		$('#gnb .gnb_li .gnb_oparea_wr .gnb_oparea').slideUp();
		var x = $(this).siblings('.gnb_oparea_wr').find('> .gnb_oparea');
		if(x.is(":visible")){
			x.slideUp();
		}else{
			x.slideDown();
		}
	});
});