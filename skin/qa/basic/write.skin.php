<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$depth1 = 5;

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$qa_skin_url.'/style.css">', 0);
?>

<section class="subpage qa pbt100" id="bo_w">
<div class="inner_container">
    <h2>1:1문의 작성</h2>
    <!-- 게시물 작성/수정 시작 { -->
    <form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="w" value="<?php echo $w ?>">
    <input type="hidden" name="qa_id" value="<?php echo $qa_id ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="token" value="<?php echo $token ?>">
		<input type="hidden" name="qa_subject" value="상담 문의가 접수되었습니다.">
    <?php
    $option = '';
    $option_hidden = '';
    $option = '';

    if ($is_dhtml_editor) {
        $option_hidden .= '<input type="hidden" name="qa_html" value="1">';
    } else {
        $option .= "\n".'<input type="checkbox" id="qa_html" name="qa_html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'>'."\n".'<label for="qa_html">html</label>';
    }

    echo $option_hidden;
    ?>
		<div class="logo_title">
			<div class="imgbox"><img src="<?= G5_SKIN_URL; ?>/qa/basic/img/qa_logo.png" alt="logo"></div>
			<div class="dfbox mbt70">
				<div class="left"><p class="fs_26 fw_600">스트라우만 대표번호<span class="fs_14 fw_300">평일 09:00 ~ 18:00 / 점심 12:00 ~ 13:00</span></p></div>
				<div class="right"><p class="fs_32 fw_600">02-2149-3800</p></div>
			</div>
		</div>
    <div class="form_01">
			<h3 class="fs_20 fw_500">온라인 문의</h3>	
        <ul>
        <?php if ($category_option) { ?>
            <li class="bo_w_select write_div">
                <div class="bo_w_name dfbox">
                    <label for="qa_category" class="title">문의 유형</label>
                    <select name="qa_category" id="qa_category" class="frm_input full_input" style="width:calc(100% - 105px)" required >
                        <option value="">선택</option>
                        <?php echo $category_option ?>
                    </select>
                </div>
            </li>
            <?php } ?>
            <li class="dfbox row2">
                <div class="bo_w_name dfbox">
                    <label for="qa_hp" class="title">이름</label>
                    <input type="text" name="qa_name" id="qa_name" class="frm_input full_input" size="30" placeholder="이름" value="<?php echo get_text($member['mb_name']); ?>">
                </div>
                <?php if ($is_hp) { ?>
                <div class="bo_w_hp dfbox">
                    <label for="qa_hp" class="title">연락처</label>
                    <input type="text" name="qa_hp" value="<?php echo get_text($write['qa_hp']); ?>" id="qa_hp" <?php echo $req_hp; ?> class="<?php echo $req_hp.' '; ?>frm_input full_input" size="30" placeholder="휴대폰">
                </div>
                <?php } ?>
            </li>

						<?php if ($is_email) { ?>
            <li class="bo_w_mail chk_box">
							<div class="dfbox">
                <label for="qa_email" class="title">이메일</label>
                <input type="text" name="qa_email" value="<?php echo get_text($write['qa_email']); ?>" id="qa_email" <?php echo $req_email; ?> class="<?php echo $req_email.' '; ?>frm_input full_input email" size="50" maxlength="100" placeholder="이메일">
							</div>
                <input type="checkbox" name="qa_email_recv" id="qa_email_recv" value="1" <?php if($write['qa_email_recv']) echo 'checked="checked"'; ?> class="selec_chk">
                <label for="qa_email_recv" class="frm_info"><span></span>답변받기</label>
            </li/>
            <?php } ?>

            <li class="qa_content_wrap dfbox <?php echo $is_dhtml_editor ? $config['cf_editor'] : ''; ?>">
                <label for="qa_content" class="title">문의내용</label>
								<div class="editor_box">
                <?php echo $editor_html; // 에디터 사용시는 에디터로, 아니면 textarea 로 노출 ?>
								</div>  
            </li>
        </ul>
				<div class="chk_wrap mt50">
					<input type="checkbox" name="qa_check" id="qa_check" class="">
					<label for="qa_check"><span></span>[필수]개인정보 수집 및 이용에 동의 합니다	. <a href="javascript:void(0);" id="view_use_term">[자세히보기]</a></label>
				</div>
    </div>
    
    <div class="btn_confirm write_div">
        <!-- <a href="<?php echo $list_href; ?>" class="btn_cancel btn">취소</a> -->
        <button type="submit" id="btn_submit" accesskey="s" class="btn_submit btn">온라인 문의 접수</button>
    </div>
    
    </form>

<div id="use_term_pop" class="modal_popup_outter">
		<div class="modal_pop_wrap privacy">
			<button data-iziModal-close class="close_btn">×</button>
			<div class="pop_title">
            	<h3>개인 정보 보호 정책</h3>
        	</div>
			<div class="contents">
				<?php include(G5_THEME_PATH."/sub/privacy_form.php")?>
			</div>
			<div class="center_align">
				<a href="javascript:void(0);" data-iziModal-close class="btn-type modify single">확인</a>
			</div>
		</div>
</div>
<script>
$(document).ready(function(){
	$('.header_inner .menu ul.gnb_dep1 > li:last-child').addClass('on');
	// modal popup 
	$(document).on('click', '#view_use_term', function (event) {
		event.preventDefault();
		$('#use_term_pop').iziModal('open', this); // Do not forget the "this"
	});

	$("#use_term_pop").iziModal({
		title: '',
		subtitle: '',
		theme: '',
		headerColor: '',
		overlayColor: 'rgba(0, 0, 0, .5)',
		iconColor: '',
		iconClass: null,
		borderBottom: false,	
		width: 750,
		padding: 0,
		overlayClose: true,
		closeOnEscape: true,
		bodyOverflow: false,
		transitionIn: 'comingIn',
		transitionOut: 'comingOut'
	});
});
</script>
    <script>
    function html_auto_br(obj)
    {
        if (obj.checked) {
            result = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.");
            if (result)
                obj.value = "2";
            else
                obj.value = "1";
        }
        else
            obj.value = "";
    }

    function fwrite_submit(f)
    {
        <?php echo $editor_js; // 에디터 사용시 자바스크립트에서 내용을 폼필드로 넣어주며 내용이 입력되었는지 검사함   ?>
                
        if(f.qa_name.value == ""){
            alert("이름을 입력해주세요.");
            f.qa_name.focus();
            return false;
        }
        if(f.qa_hp.value == ""){
            alert("연락처를 입력해주세요.");
            f.qa_hp.focus();
            return false;
        }
        if($("input[name=qa_check]").is(":checked") == false) {
            alert("개인정보 수집 및 이용에 동의가 필요합니다.");
            return false;
        }

        var subject = "";
        var content = "";
        $.ajax({
            url: g5_bbs_url+"/ajax.filter.php",
            type: "POST",
            data: {
                "subject": f.qa_subject.value,
                "content": f.qa_content.value
            },
            dataType: "json",
            async: false,
            cache: false,
            success: function(data, textStatus) {
                subject = data.subject;
                content = data.content;
            }
        });

        if (subject) {
            alert("제목에 금지단어('"+subject+"')가 포함되어있습니다");
            f.qa_subject.focus();
            return false;
        }

        if (content) {
            alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
            if (typeof(ed_qa_content) != "undefined")
                ed_qa_content.returnFalse();
            else
                f.qa_content.focus();
            return false;
        }

        <?php if ($is_hp) { ?>
        var hp = f.qa_hp.value.replace(/[0-9\-]/g, "");
        if(hp.length > 0) {
            alert("휴대폰번호는 숫자, - 으로만 입력해 주십시오.");
            return false;
        }
        <?php } ?>

        $.ajax({
            type: "POST",
            url: g5_bbs_url+"/ajax.write.token.php",
            data: { 'token_case' : 'qa_write' },
            cache: false,
            async: false,
            dataType: "json",
            success: function(data) {
                if (typeof data.token !== "undefined") {
                    token = data.token;

                    if(typeof f.token === "undefined")
                        $(f).prepend('<input type="hidden" name="token" value="">');

                    $(f).find("input[name=token]").val(token);
                }
            }
        });

        document.getElementById("btn_submit").disabled = "disabled";

        return true;
    }
    </script>
		</div>
</section>
<!-- } 게시물 작성/수정 끝 -->