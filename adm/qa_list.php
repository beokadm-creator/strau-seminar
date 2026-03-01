<?php
$sub_menu = "300991";
include_once('./_common.php');

$g5['title'] = '문의현황';

$qaconfig = get_qa_config();

$token = '';
if( $is_admin ){
    $token = _token();
    set_session('ss_qa_delete_token', $token);
}

include_once(G5_ADMIN_PATH.'/admin.head.php');

$qa_skin_path = get_skin_path('qa', (G5_IS_MOBILE ? $qaconfig['qa_mobile_skin'] : $qaconfig['qa_skin']));
$qa_skin_url  = get_skin_url('qa', (G5_IS_MOBILE ? $qaconfig['qa_mobile_skin'] : $qaconfig['qa_skin']));
$skin_file = $qa_skin_path.'/list.skin.adm.php';
?>

<iframe src="<?php echo G5_URL ?>/bbs/qalist" frameborder="0" scrolling="no" width="100%" id="iframe_board"></iframe>


<style>
  #iframe_board {margin-top: 60px;}
  .bo_v_btn {display:none;}
</style>
<script>
$(document).ready(function(){
	
	$('#iframe_board').load(function() {
		$(this).contents().find('#hd, #ft, footer, .comm_snsWrap, .board_header_wrap').hide();
		$(this).height($(this).contents().find('body')[0].scrollHeight+30);
		$(this).contents().find('.subpage.qa').css('padding','0');

		$('#iframe_board').contents().find('.bo_tit a').click(function(){
			setInterval(function () {
				$('#iframe_board').height($('#iframe_board').contents().find('body')[0].scrollHeight+30);
				}, 300);
		});
	});
});
</script>
<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php'); ?>