<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
add_javascript('<script src="'.G5_JS_URL.'/jquery.register_form.js"></script>', 0);
if ($config['cf_cert_use'] && ($config['cf_cert_simple'] || $config['cf_cert_ipin'] || $config['cf_cert_hp']))
    add_javascript('<script src="'.G5_JS_URL.'/certify.js?v='.G5_JS_VER.'"></script>', 0);

$mode = 'update_241024';
?>

<!-- 회원정보 입력/수정 시작 { -->

<div class="register subpage pbt100">
<div class="inner_container">
	<form id="fregisterform" name="fregisterform" action="<?php echo $register_action_url ?>" onsubmit="return fregisterform_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="w" value="<?php echo $w ?>">
	<input type="hidden" name="url" value="<?php echo $urlencode ?>">
	<input type="hidden" name="agree" value="<?php echo $agree ?>">
	<input type="hidden" name="agree2" value="<?php echo $agree2 ?>">
	<input type="hidden" name="cert_type" value="<?php echo $member['mb_certify']; ?>">
	<input type="hidden" name="cert_no" value="">
	<?php if (isset($member['mb_sex'])) {  ?><input type="hidden" name="mb_sex" value="<?php echo $member['mb_sex'] ?>"><?php }  ?>
	<div class="logo_title">
		<div class="imgbox"><img src="../<?php echo G5_SKIN_DIR ?>/member/basic/img/register_logo.png" alt="logo"></div>
		<h2 class="fs_44 fw_500"><?php echo $g5['title'] ?></h2>
	</div>
	<div id="register_form" class="form_01 mt60">   
	    <div class="register_form_inner">
	        <ul>
                <?php if($mode=='update_241024'){ ?>
	            <li class="id_auth">
					<div class="dfbox id_wrap ais">
	                	<label for="reg_mb_id">아이디</label>
						<div class="input_btn dfbox">
							<input type="text" name="mb_id" value="<?php echo $member['mb_id'] ?>" id="reg_mb_id" <?php echo $required ?> <?php echo $readonly ?> class="frm_input full_input <?php echo $required ?> <?php echo $readonly ?>" minlength="3" maxlength="50" placeholder="이메일 주소를 입력하세요. 아이디는 이메일 주소로 사용됩니다." style="width:100%;">

						</div>
					</div>


	            </li>
                <?php }else{ ?>
                <li class="id_auth">
                    <div class="dfbox id_wrap ais">
                        <label for="reg_mb_id">아이디</label>
                        <div class="input_btn dfbox">
                            <input type="text" name="mb_id" value="<?php echo $member['mb_id'] ?>" id="reg_mb_id" <?php echo $required ?> <?php echo $readonly ?> class="frm_input full_input <?php echo $required ?> <?php echo $readonly ?>" minlength="3" maxlength="50" placeholder="이메일 주소를 입력하세요. 아이디는 이메일 주소로 사용됩니다." style="<?=$w == 'u' ? 'width:100%;':''?>">
                            <?php if ($w == '') {  ?>
                                <button type="button" id="email_auth_btn" class="btn_frmline">이메일 인증</button>
                            <?php } ?>
                        </div>
                    </div>
                    <?php if ($w == '') {  ?>
                        <div class="dfbox ais auth_wrap">
                            <div class="empty"></div>
                            <div class="input_btn dfbox">
                                <input type="text" name="mb_auth" value="" id="reg_mb_auth" <?php echo $required ?> <?php echo $readonly ?> class="frm_input full_input <?php echo $required ?> <?php echo $readonly ?>" minlength="3" maxlength="50" placeholder="인증번호">
                                <button type="button" id="email_auth_chk_btn" class="btn_frmline">확인</button>
                            </div>
                        </div>
                    <?php } ?>

                </li>
                <?php } ?>

	            <li class="dfbox">
	                <label for="reg_mb_password">비밀번호</label>
	                <input type="password" name="mb_password" id="reg_mb_password" <?php echo $required ?> class="frm_input full_input <?php echo $required ?>" minlength="3" maxlength="20" placeholder="비밀번호">
	            </li>
	            <li class="dfbox">
	                <label for="reg_mb_password_re">비밀번호 확인</label>
	                <input type="password" name="mb_password_re" id="reg_mb_password_re" <?php echo $required ?> class="frm_input full_input <?php echo $required ?>" minlength="3" maxlength="20" placeholder="비밀번호 확인">
	            </li>
	        </ul>
	    
	        <ul>
				<li class="dfbox ais">
					<label for="reg_mb_name">성함</label>
					 <div class="input_btn dfbox addBox">
	        <input type="text" id="reg_mb_name" name="mb_name" value="<?php echo get_text($member['mb_name']) ?>" <?php echo $required ?> <?php echo $name_readonly; ?> class="frm_input full_input <?php echo $required ?> <?php echo $name_readonly ?>" size="9" placeholder="본인인증 이후 자동으로 입력됩니다">
          <?php
                if($mode=='update_241024'){
					$desc_name = '';
					$desc_phone = '';
						                if ($config['cf_cert_use']) {
					                        $desc_name = '<span class="cert_desc"> 본인확인 시 자동입력</span>';
					                        $desc_phone = '<span class="cert_desc"> 본인확인 시 자동입력</span>';
					
					                        if (!$config['cf_cert_simple'] && !$config['cf_cert_hp'] && $config['cf_cert_ipin']) {
					                            $desc_phone = '';
					                        }
					
						                    if ($config['cf_cert_simple']) {
					                            echo '<button type="button" id="win_sa_kakao_cert" class="btn_frmline win_sa_cert" data-type="">간편인증</button>'.PHP_EOL;
						}
						if($config['cf_cert_hp'])
							echo '<button type="button" id="win_hp_cert" class="btn_frmline">본인인증</button>'.PHP_EOL;
						if ($config['cf_cert_ipin'])
							echo '<button type="button" id="win_ipin_cert" class="btn_frmline">아이핀 본인확인</button>'.PHP_EOL;
						
						                    echo '<noscript>본인확인을 위해서는 자바스크립트 사용이 가능해야합니다.</noscript>'.PHP_EOL;
						                }
						                ?>
						                <?php
						                if ($config['cf_cert_use'] && $member['mb_certify']) {
						switch  ($member['mb_certify']) {
							case "simple": 
								$mb_cert = "간편인증";
								break;
							case "ipin": 
								$mb_cert = "아이핀";
								break;
							case "hp": 
								$mb_cert = "휴대폰";
								break;
						}
						                ?>
					<?php }
                }
                ?>
					 </div>
                <li class="dfbox">
                    <?php if ($config['cf_use_hp'] || ($config["cf_cert_use"] && ($config['cf_cert_hp'] || $config['cf_cert_simple']))) {  ?>
                        <label for="reg_mb_hp">휴대전화번호</label>

                        <input type="text" name="mb_hp" value="<?php echo get_text($member['mb_hp']) ?>" id="reg_mb_hp" <?php echo $hp_required; ?> <?php echo $hp_readonly; ?> class="frm_input full_input <?php echo $hp_required; ?> <?php echo $hp_readonly; ?>" maxlength="20" placeholder="본인인증 이후 자동으로 입력됩니다.">
                        <?php if ($config['cf_cert_use'] && ($config['cf_cert_hp'] || $config['cf_cert_simple'])) { ?>
                            <input type="hidden" name="old_mb_hp" value="<?php echo get_text($member['mb_hp']) ?>">
                        <?php } ?>
                    <?php }else{ ?>
                        <label for="reg_mb_hp">휴대전화번호</label>
                        <input type="text" name="mb_hp" value="<?php echo get_text($member['mb_hp']) ?>" <?php echo $required ?> id="reg_mb_hp" <?php echo $hp_required; ?> <?php echo $hp_readonly; ?> class="frm_input full_input <?php echo $required ?> <?php echo $hp_required; ?> <?php echo $hp_readonly; ?>" maxlength="20" placeholder="">
                    <?php } ?>
                </li>
				</li>
							<li class="dfbox ais">
								<label>직업</label>
								<div class="radio_box dfbox">
									<input type="radio" id="mb_1-1" name="mb_1" value="치과의사" <?=$member['mb_1'] == '치과의사' || $member['mb_1'] == '' ? 'checked':''?>>
									<label for="mb_1-1"><span class="radio_figure"></span>치과의사</label>
									<input type="radio" id="mb_1-2" name="mb_1" value="치과위생사" <?=$member['mb_1'] == '치과위생사' ? 'checked':''?>>
									<label for="mb_1-2"><span class="radio_figure"></span>치과위생사</label>
									<input type="radio" id="mb_1-3" name="mb_1" value="기공사" <?=$member['mb_1'] == '기공사' ? 'checked':''?>>
									<label for="mb_1-3"><span class="radio_figure"></span>기공사</label>
									<input type="radio" id="mb_1-4" name="mb_1" value="학생" <?=$member['mb_1'] == '학생' ? 'checked':''?>>
									<label for="mb_1-4"><span class="radio_figure"></span>학생</label>
									<input type="radio" id="mb_1-5" name="mb_1" value="간호조무사" <?=$member['mb_1'] == '간호조무사' ? 'checked':''?>>
									<label for="mb_1-5"><span class="radio_figure"></span>간호조무사</label>
								</div>

							</li>
							<li class="dfbox">
								<label for="mb_2">면허번호</label>
								<input type="text" name="mb_2" id="reg_mb_2" value="<?php echo $member['mb_2'] ?>" <?php echo $required ?> class="frm_input full_input number_only  <?php echo $required ?> <?php echo $hp_required; ?>">
							</li>

							<li class="dfbox">
								<label for="mb_3">치과명</label>
								<input type="text" name="mb_3" id="mb_3" value="<?php echo $member['mb_3'] ?>" <?php echo $required ?> class="frm_input full_input <?php echo $required ?>">
							</li>
							<li class="dfbox">
								<label for="mb_4">지역</label>
								<!--2024.08.22 이다혜 지역 입력방식 수정-->
								
								<!-- <input type="text" name="mb_4" id="mb_4" value="<?php echo $member['mb_4'] ?>" <?php echo $required ?> class="frm_input full_input <?php echo $required ?>" placeholder="ex. 서울시 강남구"> -->

								<div class="input_btn dfbox addBox">
									<input type="button" onclick="sample4_execDaumPostcode()"  value="우편번호 찾기" class="btn_frmline">
									<input type="text" name="mb_4" id="mb_4" value="<?php echo $member['mb_4'] ?>" <?php echo $required ?> class="frm_input full_input <?php echo $required ?>" placeholder="ex. 서울시 강남구">	
								</div>
								<span id="guide" style="color:#999;display:none"></span>
								<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
								<script>
									 function sample4_execDaumPostcode() {
										  new daum.Postcode({
												oncomplete: function(data) {

													console.log(data)
													

													 var ext_field= data.sido + " " + data.sigungu;
													 // 주소 정보를 해당 필드에 넣는다.
													 document.getElementById("mb_4").value = ext_field;
													 var guideTextBox = document.getElementById("guide");
													 // 사용자가 '선택 안함'을 클릭한 경우, 예상 주소라는 표시를 해준다.
												}
										  }).open();
									 }
								</script>


							</li>
							<li class="dfbox ais checkvalue">
								<label>스트라우만 그룹 <br>브랜드제품을 <br>사용하시고 계신가요?</label>
                                <div class="checkbox_box dfbox">
                                    <div class="checkbox_item">
                                    <input type="checkbox" id="mb_5-1" name="mb_5[]" value="스트라우만 임플란트" <?= in_array('스트라우만 임플란트', explode(',', $member['mb_5'])) ? 'checked' : '' ?> >
                                    <label for="mb_5-1"><span class="checkbox_figure"></span>&nbsp;스트라우만 임플란트</label> &nbsp;&nbsp;&nbsp;
                                    </div>
                                    <div class="checkbox_item">
                                    <input type="checkbox" id="mb_5-2" name="mb_5[]" value="앤서지 임플란트" <?= in_array('앤서지 임플란트', explode(',', $member['mb_5'])) ? 'checked' : '' ?> >
                                    <label for="mb_5-2"><span class="checkbox_figure"></span>&nbsp;앤서지 임플란트</label>&nbsp;&nbsp;&nbsp;
                                    </div>
                                    <div class="checkbox_item">
                                    <input type="checkbox" id="mb_5-3" name="mb_5[]" value="디지털 장비" <?= in_array('디지털 장비', explode(',', $member['mb_5'])) ? 'checked' : '' ?> >
                                    <label for="mb_5-3"><span class="checkbox_figure"></span>&nbsp;디지털 장비</label>&nbsp;&nbsp;&nbsp;
                                    </div>
                                    <div class="checkbox_item">
                                    <input type="checkbox" id="mb_5-4" name="mb_5[]" value="바이오머테리얼" <?= in_array('바이오머테리얼', explode(',', $member['mb_5'])) ? 'checked' : '' ?> >
                                    <label for="mb_5-4"><span class="checkbox_figure"></span>&nbsp;바이오머테리얼</label>&nbsp;&nbsp;&nbsp;
                                    </div>
                                    <div class="checkbox_item">
                                    <input type="checkbox" id="mb_5-5" name="mb_5[]" value="사용안함" <?= in_array('사용안함', explode(',', $member['mb_5'])) ? 'checked' : '' ?> >
                                    <label for="mb_5-5"><span class="checkbox_figure"></span>&nbsp;사용안함</label>
                                    </div>
                                </div>
							</li>



                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const checkboxes = document.querySelectorAll('input[name="mb_5[]"]');
                        const notUsedCheckbox = document.querySelector('#mb_5-5');

                        checkboxes.forEach(function(checkbox) {
                            checkbox.addEventListener('change', function() {
                                if (checkbox === notUsedCheckbox && notUsedCheckbox.checked) {
                                    checkboxes.forEach(function(cb) {
                                        if (cb !== notUsedCheckbox) {
                                            cb.checked = false;
                                        }
                                    });
                                } else if (checkbox !== notUsedCheckbox && checkbox.checked) {
                                    notUsedCheckbox.checked = false;
                                }
                            });
                        });
                    });
                </script>

                        <li class="dfbox ais checkvalue requir">
                            <label class="required">어떤 경로를 통해 <br>들어오셨나요?</label>
                            <div class="checkbox_box dfbox">
                                    <div class="checkbox_item">
                                        <input type="checkbox" id="mb_6-1" name="mb_6[]" value="담당영업사원" <?= in_array('담당영업사원', explode(',', $member['mb_6'])) ? 'checked' : '' ?> >
                                        <label for="mb_6-1"><span class="checkbox_figure"></span>담당영업사원</label>
                                    </div>

                                    <div class="checkbox_item">
                                        <input type="checkbox" id="mb_6-2" name="mb_6[]" value="검색광고(네이버 등)" <?= in_array('검색광고(네이버 등)', explode(',', $member['mb_6'])) ? 'checked' : '' ?> >
                                        <label for="mb_6-2"><span class="checkbox_figure"></span>검색광고(네이버 등)</label>
                                    </div>

                                    <div class="checkbox_item">
                                        <input type="checkbox" id="mb_6-3" name="mb_6[]" value="덴탈지(치의신보,치과신문 등)" <?= in_array('덴탈지(치의신보,치과신문 등)', explode(',', $member['mb_6'])) ? 'checked' : '' ?> >
                                        <label for="mb_6-3"><span class="checkbox_figure"></span>덴탈지(치의신보,치과신문 등)</label>
                                    </div>

                                    <div class="checkbox_item">
                                        <input type="checkbox" id="mb_6-4" name="mb_6[]" value="덴탈2804배너" <?= in_array('덴탈2804배너', explode(',', $member['mb_6'])) ? 'checked' : '' ?> >
                                        <label for="mb_6-4"><span class="checkbox_figure"></span>덴탈2804배너</label>
                                    </div>

                                    <div class="checkbox_item">
                                        <input type="checkbox" id="mb_6-5" name="mb_6[]" value="SNS (페이스북,카카오톡)" <?= in_array('SNS (페이스북,카카오톡)', explode(',', $member['mb_6'])) ? 'checked' : '' ?> >
                                        <label for="mb_6-5"><span class="checkbox_figure"></span>SNS (페이스북,카카오톡)</label>
                                    </div>

                                    <div class="checkbox_item">
                                        <input type="checkbox" id="mb_6-6" name="mb_6[]" value="유튜브 광고" <?= in_array('유튜브 광고', explode(',', $member['mb_6'])) ? 'checked' : '' ?> >
                                        <label for="mb_6-6"><span class="checkbox_figure"></span>유튜브 광고</label>
                                    </div>

                                    <div class="checkbox_item">
                                        <input type="checkbox" id="mb_6-7" name="mb_6[]" value="지인소개" <?= in_array('지인소개', explode(',', $member['mb_6'])) ? 'checked' : '' ?> >
                                        <label for="mb_6-7"><span class="checkbox_figure"></span>지인소개</label>
                                    </div>

                                    <div class="checkbox_item">
                                        <input type="checkbox" id="mb_6-8" name="mb_6[]" value="기타" <?= in_array('기타', explode(',', $member['mb_6'])) ? 'checked' : '' ?> >
                                        <label for="mb_6-8"><span class="checkbox_figure"></span>기타</label>
                                        &nbsp; <input type="text" id="mb_7" name="mb_7" value="<?= isset($member['mb_7']) ? htmlspecialchars($member['mb_7']) : '' ?>">
                                    </div>


                            </div>
                        </li>
	            <li>
                    
	            <?php if ($config['cf_use_tel']) {  ?>
	            
	                <label for="reg_mb_tel">전화번호<?php if ($config['cf_req_tel']) { ?> (필수)<?php } ?></label>
	                <input type="text" name="mb_tel" value="<?php echo get_text($member['mb_tel']) ?>" id="reg_mb_tel" <?php echo $config['cf_req_tel']?"required":""; ?> class="frm_input full_input <?php echo $config['cf_req_tel']?"required":""; ?>" maxlength="20" placeholder="전화번호">
	            <?php }  ?>
				</li>
				
	
	            <?php if ($config['cf_use_addr']) { ?>
	            <li>
	            	<label>주소</label>
					<?php if ($config['cf_req_addr']) { ?> (필수)<?php }  ?>
	                <label for="reg_mb_zip" class="sound_only">우편번호<?php echo $config['cf_req_addr']?' (필수)':''; ?></label>
	                <input type="text" name="mb_zip" value="<?php echo $member['mb_zip1'].$member['mb_zip2']; ?>" id="reg_mb_zip" <?php echo $config['cf_req_addr']?"required":""; ?> class="frm_input twopart_input <?php echo $config['cf_req_addr']?"required":""; ?>" size="5" maxlength="6"  placeholder="우편번호">
	                <button type="button" class="btn_frmline" onclick="win_zip('fregisterform', 'mb_zip', 'mb_addr1', 'mb_addr2', 'mb_addr3', 'mb_addr_jibeon');">주소 검색</button><br>
	                <input type="text" name="mb_addr1" value="<?php echo get_text($member['mb_addr1']) ?>" id="reg_mb_addr1" <?php echo $config['cf_req_addr']?"required":""; ?> class="frm_input frm_address full_input <?php echo $config['cf_req_addr']?"required":""; ?>" size="50"  placeholder="기본주소">
	                <label for="reg_mb_addr1" class="sound_only">기본주소<?php echo $config['cf_req_addr']?' (필수)':''; ?></label><br>
	                <input type="text" name="mb_addr2" value="<?php echo get_text($member['mb_addr2']) ?>" id="reg_mb_addr2" class="frm_input frm_address full_input" size="50" placeholder="상세주소">
	                <label for="reg_mb_addr2" class="sound_only">상세주소</label>
	                <br>
	                <input type="text" name="mb_addr3" value="<?php echo get_text($member['mb_addr3']) ?>" id="reg_mb_addr3" class="frm_input frm_address full_input" size="50" readonly="readonly" placeholder="참고항목">
	                <label for="reg_mb_addr3" class="sound_only">참고항목</label>
	                <input type="hidden" name="mb_addr_jibeon" value="<?php echo get_text($member['mb_addr_jibeon']); ?>">
	            </li>
	            <?php }  ?>
        </ul>
            </div>
        <div class="referral_section" style="background:#f8f9fa; padding:15px; border-radius:5px; margin-top:15px; border:2px solid #007bff;">
            <h4 style="margin-bottom:10px; color:#495057;">추천인 찾기</h4>
            <div class="referral_tabs" style="margin-bottom:15px;">
                <button type="button" class="referral_tab_btn active" data-tab="search" style="padding:8px 15px; margin-right:5px; border:1px solid #ddd; background:#fff; cursor:pointer; border-radius:3px;">추천인 검색하기</button>
                <button type="button" class="referral_tab_btn" data-tab="code" style="padding:8px 15px; border:1px solid #ddd; background:#f8f9fa; cursor:pointer; border-radius:3px;">추천코드 입력하기</button>
            </div>
            <div class="referral_tab_content" id="referral_search_tab" style="display:block;">
                <div class="referral_search_input" style="margin-bottom:10px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <select name="search_type" id="search_type" class="frm_input" style="width:140px; min-width:140px; height:44px;">
                        <option value="name">이름검색</option>
                        <option value="clinic">병원/소속 검색</option>
                    </select>
                    <input type="text" name="search_keyword" id="search_keyword" class="frm_input" placeholder="추천인 이름을 입력하세요" style="flex:1; margin-bottom:0; height:44px;">
                    <button type="button" id="btn_referral_search" class="btn_frmline" style="width:100px; min-width:100px; white-space:nowrap; height:44px;">검색</button>
                </div>
                <div id="referral_search_result" style="font-size:12px; color:#666;"></div>
                <style>
                #referral_search_result { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:8px; }
                #referral_search_result > div { margin:0 !important; border:1px solid #e5e7eb; border-radius:8px; padding:10px; background:#fff; }
                #referral_search_result button { width:100%; min-height:40px; }
                #referral_search_pager button { min-height:40px; padding:8px 12px; }
                @media (max-width:640px){
                  #referral_search_result { grid-template-columns: 1fr; }
                  .referral_search_input { flex-direction: column; gap:8px; }
                  #search_type, #search_keyword, #btn_referral_search { width:100% !important; min-width:0 !important; }
                }
                </style>
                <div id="referral_search_pager" style="display:flex; gap:6px; align-items:center; justify-content:center; margin-top:8px;"></div>
            </div>
            <div class="referral_tab_content" id="referral_code_tab" style="display:none;">
                <div class="referral_code_input" style="margin-bottom:10px;">
                    <input type="text" name="mb_referral_code" id="reg_mb_referral_code" class="frm_input" placeholder="추천인 코드를 입력하세요" style="width:100%; margin-bottom:5px;">
                    <button type="button" id="btn_referral_check" class="btn_frmline" style="width:100%;">추천인 확인</button>
                </div>
                <div id="referral_code_result" style="font-size:13px; color:#2a7f62;"></div>
        </div>
            <input type="hidden" name="referral_code" id="referral_code" value="<?php
                $ref_init = '';
                if (isset($_POST['referral_code']) && $_POST['referral_code']) $ref_init = trim($_POST['referral_code']);
                else if (isset($_GET['referral_code']) && $_GET['referral_code']) $ref_init = trim($_GET['referral_code']);
                else if (function_exists('get_session')) { $ref_sess = get_session('referral_code'); if ($ref_sess) $ref_init = $ref_sess; }
                echo htmlspecialchars($ref_init);
            ?>">
        </div>
	    <div class="tbl_frm01 tbl_wrap register_form_inner">
	        <h2>자동등록방지</h2>
	        <ul>
	            <!-- <?php if ($config['cf_use_signature']) {  ?>
	            <li>
	                <label for="reg_mb_signature">서명<?php if ($config['cf_req_signature']){ ?> (필수)<?php } ?></label>
	                <textarea name="mb_signature" id="reg_mb_signature" <?php echo $config['cf_req_signature']?"required":""; ?> class="<?php echo $config['cf_req_signature']?"required":""; ?>"   placeholder="서명"><?php echo $member['mb_signature'] ?></textarea>
	            </li>
	            <?php }  ?>
	            	
	            <?php if ($config['cf_use_profile']) {  ?>
	            <li>
	                <label for="reg_mb_profile">자기소개</label>
	                <textarea name="mb_profile" id="reg_mb_profile" <?php echo $config['cf_req_profile']?"required":""; ?> class="<?php echo $config['cf_req_profile']?"required":""; ?>" placeholder="자기소개"><?php echo $member['mb_profile'] ?></textarea>
	            </li>
	            <?php }  ?>
	            	
	            <?php if ($config['cf_use_member_icon'] && $member['mb_level'] >= $config['cf_icon_level']) {  ?>
	            <li>
	                <label for="reg_mb_icon" class="frm_label">
	                	회원아이콘
	                	<button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
	                	<span class="tooltip">이미지 크기는 가로 <?php echo $config['cf_member_icon_width'] ?>픽셀, 세로 <?php echo $config['cf_member_icon_height'] ?>픽셀 이하로 해주세요.<br>
	            gif, jpg, png파일만 가능하며 용량 <?php echo number_format($config['cf_member_icon_size']) ?>바이트 이하만 등록됩니다.</span>
	                </label>
	                <input type="file" name="mb_icon" id="reg_mb_icon">
	            	
	                <?php if ($w == 'u' && file_exists($mb_icon_path)) {  ?>
	                <img src="<?php echo $mb_icon_url ?>" alt="회원아이콘">
	                <input type="checkbox" name="del_mb_icon" value="1" id="del_mb_icon">
	                <label for="del_mb_icon" class="inline">삭제</label>
	                <?php }  ?>
	            
	            </li>
	            <?php }  ?>
	            	
	            <?php if ($member['mb_level'] >= $config['cf_icon_level'] && $config['cf_member_img_size'] && $config['cf_member_img_width'] && $config['cf_member_img_height']) {  ?>
	            <li class="reg_mb_img_file">
	                <label for="reg_mb_img" class="frm_label">
	                	회원이미지
	                	<button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
	                	<span class="tooltip">이미지 크기는 가로 <?php echo $config['cf_member_img_width'] ?>픽셀, 세로 <?php echo $config['cf_member_img_height'] ?>픽셀 이하로 해주세요.<br>
	                    gif, jpg, png파일만 가능하며 용량 <?php echo number_format($config['cf_member_img_size']) ?>바이트 이하만 등록됩니다.</span>
	                </label>
	                <input type="file" name="mb_img" id="reg_mb_img">
	            	
	                <?php if ($w == 'u' && file_exists($mb_img_path)) {  ?>
	                <img src="<?php echo $mb_img_url ?>" alt="회원이미지">
	                <input type="checkbox" name="del_mb_img" value="1" id="del_mb_img">
	                <label for="del_mb_img" class="inline">삭제</label>
	                <?php }  ?>
	            
	            </li>
	            <?php } ?>
	            
	            <li class="chk_box">
	            		        	<input type="checkbox" name="mb_mailling" value="1" id="reg_mb_mailling" <?php echo ($w=='' || $member['mb_mailling'])?'checked':''; ?> class="selec_chk">
	            		            <label for="reg_mb_mailling">
	            		            	<span></span>
	            		            	<b class="sound_only">메일링서비스</b>
	            		            </label>
	            		            <span class="chk_li">정보 메일을 받겠습니다.</span>
	            		        </li>
	            	
	            				<?php if ($config['cf_use_hp']) { ?>
	            		        <li class="chk_box">
	            		            <input type="checkbox" name="mb_sms" value="1" id="reg_mb_sms" <?php echo ($w=='' || $member['mb_sms'])?'checked':''; ?> class="selec_chk">
	            		        	<label for="reg_mb_sms">
	            		            	<span></span>
	            		            	<b class="sound_only">SMS 수신여부</b>
	            		            </label>        
	            		            <span class="chk_li">휴대폰 문자메세지를 받겠습니다.</span>
	            		        </li>
	            		        <?php } ?>
	            	
	            		        <?php if (isset($member['mb_open_date']) && $member['mb_open_date'] <= date("Y-m-d", G5_SERVER_TIME - ($config['cf_open_modify'] * 86400)) || empty($member['mb_open_date'])) { // 정보공개 수정일이 지났다면 수정가능 ?>
	            		        <li class="chk_box">
	            		            <input type="checkbox" name="mb_open" value="1" id="reg_mb_open" <?php echo ($w=='' || $member['mb_open'])?'checked':''; ?> class="selec_chk">
	            		      		<label for="reg_mb_open">
	            		      			<span></span>
	            		      			<b class="sound_only">정보공개</b>
	            		      		</label>      
	            		            <span class="chk_li">다른분들이 나의 정보를 볼 수 있도록 합니다.</span>
	            		            <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
	            		            <span class="tooltip">
	            		                정보공개를 바꾸시면 앞으로 <?php echo (int)$config['cf_open_modify'] ?>일 이내에는 변경이 안됩니다.
	            		            </span>
	            		            <input type="hidden" name="mb_open_default" value="<?php echo $member['mb_open'] ?>"> 
	            		        </li>		        
	            		        <?php } else { ?>
	            <li>
	                정보공개
	                <input type="hidden" name="mb_open" value="<?php echo $member['mb_open'] ?>">
	                <button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
	                <span class="tooltip">
	                    정보공개는 수정후 <?php echo (int)$config['cf_open_modify'] ?>일 이내, <?php echo date("Y년 m월 j일", isset($member['mb_open_date']) ? strtotime("{$member['mb_open_date']} 00:00:00")+$config['cf_open_modify']*86400:G5_SERVER_TIME+$config['cf_open_modify']*86400); ?> 까지는 변경이 안됩니다.<br>
	                    이렇게 하는 이유는 잦은 정보공개 수정으로 인하여 쪽지를 보낸 후 받지 않는 경우를 막기 위해서 입니다.
	                </span>
	                
	            </li>
	            <?php }  ?>
	            	
	            <?php
	            //회원정보 수정인 경우 소셜 계정 출력
	            if( $w == 'u' && function_exists('social_member_provider_manage') ){
	                social_member_provider_manage();
	            }
	            ?>
	            
	            <?php if ($w == "" && $config['cf_use_recommend']) {  ?>
	            <li>
	                <label for="reg_mb_recommend" class="sound_only">추천인아이디</label>
	                <input type="text" name="mb_recommend" id="reg_mb_recommend" class="frm_input" placeholder="추천인아이디">
	            </li>
	            <?php }  ?> -->
	
	            <li class="is_captcha_use">
	                <!-- 자동등록방지 -->
	                <?php echo captcha_html(); ?>
	            </li>
	        </ul>
	    </div>
	</div>
	<div class="btn_confirm">
	    <!-- <a href="<?php echo G5_URL ?>" class="btn_close">취소</a> -->
	    <button type="submit" id="btn_submit" class="btn_submit" accesskey="s"><?php echo $w==''?'회원가입 신청':'정보수정'; ?></button>
	</div>
	</form>
	</div>
</div>
<script>
jQuery(function($) {
    $("#reg_zip_find").css("display", "inline-block");
    var pageTypeParam = "pageType=register";

	<?php if($config['cf_cert_use'] && $config['cf_cert_simple']) { ?>
	// 이니시스 간편인증
	var url = "<?php echo G5_INICERT_URL; ?>/ini_request.php";
	var type = "";    
    var params = "";
    var request_url = "";

	$(".win_sa_cert").click(function() {
		if(!cert_confirm()) return false;
		type = $(this).data("type");
        params = "?directAgency=" + type + "&" + pageTypeParam;
        request_url = url + params;
        call_sa(request_url);
	});
    <?php } ?>
    <?php if($config['cf_cert_use'] && $config['cf_cert_ipin']) { ?>
    // 아이핀인증
    var params = "";
    $("#win_ipin_cert").click(function() {
		if(!cert_confirm()) return false;
        params = "?" + pageTypeParam;
        var url = "<?php echo G5_OKNAME_URL; ?>/ipin1.php"+params;
        certify_win_open('kcb-ipin', url);
        return;
    });

    <?php } ?>
    <?php if($config['cf_cert_use'] && $config['cf_cert_hp']) { ?>
    // 휴대폰인증
    var params = "";
    $("#win_hp_cert").click(function() {
		if(!cert_confirm()) return false;
        params = "?" + pageTypeParam;
        <?php     
        switch($config['cf_cert_hp']) {
            case 'kcb':                
                $cert_url = G5_OKNAME_URL.'/hpcert1.php';
                $cert_type = 'kcb-hp';
                break;
            case 'kcp':
                $cert_url = G5_KCPCERT_URL.'/kcpcert_form.php';
                $cert_type = 'kcp-hp';
                break;
            case 'lg':
                $cert_url = G5_LGXPAY_URL.'/AuthOnlyReq.php';
                $cert_type = 'lg-hp';
                break;
            default:
                echo 'alert("기본환경설정에서 휴대폰 본인확인 설정을 해주십시오");';
                echo 'return false;';
                break;
        }
        ?>
        
        certify_win_open("<?php echo $cert_type; ?>", "<?php echo $cert_url; ?>"+params);
        return;
    });
    <?php } ?>
});

var email_chk = false;
// submit 최종 폼체크
function fregisterform_submit(f)
{
    // 회원아이디 검사
    if (f.w.value == "") {
        var msg = reg_mb_id_check();
        if (msg) {
            alert(msg);
            f.mb_id.select();
            return false;
        }
    }

	/* [OSJ : 2024-03-14] 이메일 인증 추가 & 이메일 인증은 회원가입시에만 사용됨 */
	<?php if ($w == '' && $mode != 'update_241024') {  ?>
	if(email_chk == false){
		alert("이메일 인증을 해주셔야 합니다.");
		return false;
	}
	<?php } ?>

    if (f.w.value == "") {
        if (f.mb_password.value.length < 3) {
            alert("비밀번호를 3글자 이상 입력하십시오.");
            f.mb_password.focus();
            return false;
        }
    }

    if (f.mb_password.value != f.mb_password_re.value) {
        alert("비밀번호가 같지 않습니다.");
        f.mb_password_re.focus();
        return false;
    }

    if (f.mb_password.value.length > 0) {
        if (f.mb_password_re.value.length < 3) {
            alert("비밀번호를 3글자 이상 입력하십시오.");
            f.mb_password_re.focus();
            return false;
        }
    }

    // 이름 검사
    if (f.w.value=="") {
        if (f.mb_name.value.length < 1) {
            alert("이름을 입력하십시오.");
            f.mb_name.focus();
            return false;
        }

        /*
        var pattern = /([^가-힣\x20])/i;
        if (pattern.test(f.mb_name.value)) {
            alert("이름은 한글로 입력하십시오.");
            f.mb_name.select();
            return false;
        }
        */
    }

    <?php if($w == '' && $config['cf_cert_use'] && $config['cf_cert_req']) { ?>
    // 본인확인 체크
    if(f.cert_no.value=="") {
        alert("회원가입을 위해서는 본인확인을 해주셔야 합니다.");
        return false;
    }
    <?php } ?>

    <?php if (($config['cf_use_hp'] || $config['cf_cert_hp']) && $config['cf_req_hp']) {  ?>
    // 휴대폰번호 체크
    var msg = reg_mb_hp_check();
    if (msg) {
        alert(msg);
        f.reg_mb_hp.select();
        return false;
    }
    <?php } ?>

    if (typeof f.mb_icon != "undefined") {
        if (f.mb_icon.value) {
            if (!f.mb_icon.value.toLowerCase().match(/.(gif|jpe?g|png)$/i)) {
                alert("회원아이콘이 이미지 파일이 아닙니다.");
                f.mb_icon.focus();
                return false;
            }
        }
    }

    if (typeof f.mb_img != "undefined") {
        if (f.mb_img.value) {
            if (!f.mb_img.value.toLowerCase().match(/.(gif|jpe?g|png)$/i)) {
                alert("회원이미지가 이미지 파일이 아닙니다.");
                f.mb_img.focus();
                return false;
            }
        }
    }

    if (typeof(f.mb_recommend) != "undefined" && f.mb_recommend.value) {
        if (f.mb_id.value == f.mb_recommend.value) {
            alert("본인을 추천할 수 없습니다.");
            f.mb_recommend.focus();
            return false;
        }

        var msg = reg_mb_recommend_check();
        if (msg) {
            alert(msg);
            f.mb_recommend.select();
            return false;
        }
    }

    // 모든 체크박스를 선택된 상태인지 확인
    var checkboxes = document.querySelectorAll('input[name="mb_6[]"]');
    var isChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);

    if (!isChecked) {
        // 체크박스가 선택되지 않았을 경우
        alert('가입경로를 선택해주세요.');
        return false;
    }

    <?php echo chk_captcha_js();  ?>

    document.getElementById("btn_submit").disabled = "disabled";

    return true;
}

jQuery(function($){
	//tooltip
    $(document).on("click", ".tooltip_icon", function(e){
        $(this).next(".tooltip").fadeIn(400).css("display","inline-block");
    }).on("mouseout", ".tooltip_icon", function(e){
        $(this).next(".tooltip").fadeOut();
    });


	//영문만입력
	$("input.eng_only").on("keyup", function() {
		$(this).val($(this).val().replace(/[^a-zA-Z]/g, ""));
	});

	//한글만입력
	$("input.kr_only").on("keyup", function() {
		$(this).val($(this).val().replace(/[^\uAC00-\uD7AF\u1100-\u11FF\u3130-\u318F]/g, ""));
	});

	//숫자만입력
	$("input.number_only").on("keyup", function() {
		$(this).val($(this).val().replace(/[^0-9]/g,""));
	});

	/* [OSJ : 2024-03-14] 이메일 인증은 회원가입만 사용됨 */
	<?php if ($w == '') {  ?>
	$("#email_auth_btn").on("click", function(){
        var msg = reg_mb_id_check();
        if (msg) {
            alert(msg);
            $("#reg_mb_id").focus();
            return false;
        }
		
        $.ajax({
            url: "/bbs/ajax.email.php",
            type: "POST",
			dataType: "json",
            data: {
				"type": "mem_email_auth",
                "wr_email": $("#reg_mb_id").val(),
            },
            success: function (data) {
                console.log(data);
				if(data.error){
					alert(data.error);
					return false;
				}else{
					if(data.result == "success"){
						$("#reg_mb_id").attr("readonly", true);
						alert("이메일로 인증번호가 발송되었습니다.");
						return false;
					}
				}
            },
            error: function (request, status, error) {
                // alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"err
                // or:"+error);
            }
        });
	});

	$("#email_auth_chk_btn").on("click", function(){
		email_chk = false;

        var authNum = $("#reg_mb_auth").val();
        if (authNum == "") {
            alert("인증번호를 입력해주세요");
            $("#reg_mb_auth").focus();
            return false;
        }
		
        $.ajax({
            url: "/bbs/ajax.email.php",
            type: "POST",
			dataType: "json",
            data: {
				"type": "mem_email_auth_check",
                "auth_str": $("#reg_mb_auth").val(),
            },
            success: function (data) {
                console.log(data);
				if(data.error){
					alert(data.error);
				}else{
					if(data.result == "success"){
						email_chk = true;
						$(".auth_wrap").hide();
						$("#email_auth_btn").attr("disabled", true);
						$("#reg_mb_id").attr("readonly", true);

						alert("인증되었습니다.");
					}else if(data.result == "diff"){
						alert("인증번호를 다시 입력해주세요.");
						$("#reg_mb_auth").focus();
						return false;
					}
				}
				
            },
            error: function (request, status, error) {
                // alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"err
                // or:"+error);
            }
        });
	});
	<?php } ?>

    // 초기 추천코드 자동 설정 (약관 단계에서 전달된 경우)
    try {
        var initialRef = $('#referral_code').val();
        if(!initialRef || !initialRef.length){
            try {
                var savedRef = sessionStorage.getItem('referral_code') || localStorage.getItem('referral_code');
                if(!savedRef){ var m = document.cookie.match(/(?:^|; )referral_code=([^;]+)/); if(m && m[1]) savedRef = decodeURIComponent(m[1]); }
                if(savedRef){ initialRef = savedRef; $('#referral_code').val(savedRef); }
            } catch(e){}
        }
        if(initialRef && initialRef.length){
            // 탭을 코드 입력으로 전환
            $('.referral_tab_btn').removeClass('active').css({background:'#f8f9fa'});
            $('.referral_tab_btn[data-tab="code"]').addClass('active').css({background:'#fff'});
            $('#referral_search_tab').hide();
            $('#referral_code_tab').show();

            // 코드 입력값 채우고 검증 호출
            $('#reg_mb_referral_code').val(initialRef);
            $.post('/bbs/ajax.referral.php', { referral_code: initialRef }, function(resp){
                var json = resp;
                if(typeof resp === 'string'){
                    try { json = JSON.parse(resp); } catch(e){ json = { success:false, message:'응답 오류' }; }
                }
                if(json && json.success){
                    $('#referral_code_result').html('<div style="color:#2a7f62;">'+ json.message +'</div>');
                } else {
                    $('#referral_code_result').html('<div style="color:#b00020;">'+ (json && json.message ? json.message : '유효하지 않은 코드입니다.') +'</div>');
                }
            });
        }
    } catch(_e) {}

    // 추천인 탭 토글
    $(document).on('click', '.referral_tab_btn', function(){
        $('.referral_tab_btn').removeClass('active').css({background:'#f8f9fa'});
        $(this).addClass('active').css({background:'#fff'});
        var tab = $(this).data('tab');
        if(tab === 'search'){
            $('#referral_search_tab').show();
            $('#referral_code_tab').hide();
        } else {
            $('#referral_search_tab').hide();
            $('#referral_code_tab').show();
        }
    });

    // 추천인 검색
    var referralPage = 1;
    var referralDebounce = null;
    function renderPager(p, total){
        var $pager = $('#referral_search_pager');
        $pager.empty();
        if(!total || total <= 1) return;
        var $prev = $('<button type="button" class="btn_frmline">이전</button>');
        var $next = $('<button type="button" class="btn_frmline">다음</button>');
        var $info = $('<span/>').text(p + ' / ' + total);
        if(p <= 1) $prev.prop('disabled', true);
        if(p >= total) $next.prop('disabled', true);
        $prev.on('click', function(){ referralPage = Math.max(1, p - 1); performReferralSearch(referralPage); });
        $next.on('click', function(){ referralPage = Math.min(total, p + 1); performReferralSearch(referralPage); });
        $pager.append($prev, $info, $next);
    }
    function performReferralSearch(page){
        var type = $('#search_type').val();
        var kw = $('#search_keyword').val();
        if(!kw || kw.length < 2){ alert('검색어를 2글자 이상 입력해주세요.'); return; }
        var data = { page: page, page_size: 20 };
        $('#btn_referral_search').prop('disabled', true);
        $('#referral_search_result').html('<div style="text-align:center; color:#64748b; padding:10px;">검색 중...</div>');
        if(type === 'name'){ data.name = kw; } else { data.clinic = kw; }
        $.post('/bbs/ajax.referral_search.php', data, function(resp){
            var json;
            try{ json = (typeof resp === 'string') ? JSON.parse(resp) : resp; }catch(e){ json = { success:false, message:'검색 응답 오류' }; }
            if(json && json.success){ $('#referral_search_result').html(json.html); renderPager(json.page, json.total_pages); }
            else { $('#referral_search_result').html('<div>'+ (json && json.message ? json.message : '검색 실패') +'</div>'); $('#referral_search_pager').empty(); }
        }).always(function(){ $('#btn_referral_search').prop('disabled', false); });
    }
    $('#btn_referral_search').on('click', function(){ referralPage = 1; performReferralSearch(referralPage); });
    $('#search_keyword').on('keydown', function(e){
        if((e.key === 'Enter' || e.keyCode === 13) && !e.isComposing){
            e.preventDefault();
            referralPage = 1;
            performReferralSearch(referralPage);
        }
    });
    $('#search_type').on('change', function(){
        var t = $(this).val();
        $('#search_keyword').attr('placeholder', t==='clinic' ? '병원/소속을 입력하세요' : '추천인 이름을 입력하세요');
    }).trigger('change');
    $('#search_keyword').on('input', function(){
        var kw = this.value;
        if(referralDebounce){ clearTimeout(referralDebounce); }
        referralDebounce = setTimeout(function(){
            if(kw && kw.length >= 2){ referralPage = 1; performReferralSearch(referralPage); }
        }, 350);
    });

    // 추천 코드 확인
    $('#btn_referral_check').on('click', function(){
        var code = $('#reg_mb_referral_code').val();
        if(!code){ alert('추천인 코드를 입력하세요'); return; }
        $.post('/bbs/ajax.referral.php', { referral_code: code }, function(resp){
            try{ var json = (typeof resp === 'string') ? JSON.parse(resp) : resp; }catch(e){ json = { success:false, message:'응답 오류' }; }
            if(json && json.success){
                $('#referral_code_result').html('<div style="color:#2a7f62;">'+ json.message +'</div>');
                $('#referral_code').val(code);
            } else {
                $('#referral_code_result').html('<div style="color:#b00020;">'+ (json && json.message ? json.message : '유효하지 않은 코드입니다.') +'</div>');
                $('#referral_code').val('');
            }
        });
    });
});

// 검색 결과에서 선택
function selectReferrer(mb_id, mb_name, mb_referral_code){
    var msg = '추천인 확인됨: ' + (mb_name || mb_id);
    var hc = document.getElementById('referral_code');
    var ic = document.getElementById('reg_mb_referral_code');
    if(mb_referral_code){
        if(hc) hc.value = mb_referral_code;
        if(ic) ic.value = mb_referral_code;
        try{
            sessionStorage.setItem('referral_code', mb_referral_code);
            localStorage.setItem('referral_code', mb_referral_code);
            document.cookie = 'referral_code='+encodeURIComponent(mb_referral_code)+'; path=/; max-age='+(60*60*24*7);
        }catch(e){}
    }

    // 코드 탭으로 전환하여 피드백 표시
    try{
        var $tabs = jQuery ? jQuery('.referral_tab_btn') : null;
        if($tabs && $tabs.length){
            $tabs.removeClass('active').css({background:'#f8f9fa'});
            jQuery('.referral_tab_btn[data-tab="code"]').addClass('active').css({background:'#fff'});
            jQuery('#referral_search_tab').hide();
            jQuery('#referral_code_tab').show();
        } else {
            var btns = document.querySelectorAll('.referral_tab_btn');
            for(var i=0;i<btns.length;i++){ btns[i].classList.remove('active'); btns[i].style.background = '#f8f9fa'; }
            var codeBtn = document.querySelector('.referral_tab_btn[data-tab="code"]');
            if(codeBtn){ codeBtn.classList.add('active'); codeBtn.style.background = '#fff'; }
            var searchTab = document.getElementById('referral_search_tab');
            var codeTab = document.getElementById('referral_code_tab');
            if(searchTab) searchTab.style.display = 'none';
            if(codeTab) codeTab.style.display = 'block';
        }
    }catch(e){}

    var box = document.getElementById('referral_code_result');
    if(box){
        if(mb_referral_code){
            if(window.jQuery){
                jQuery.post('/bbs/ajax.referral.php', { referral_code: mb_referral_code }, function(resp){
                    var json = resp;
                    if(typeof resp === 'string'){
                        try{ json = JSON.parse(resp); }catch(e){ json = { success:false, message:'응답 오류' }; }
                    }
                    if(json && json.success){
                        box.innerHTML = '<div style="font-size:13px; color:#2a7f62;">'+ json.message +'</div>';
                    } else {
                        box.innerHTML = '<div style="font-size:13px; color:#b00020;">'+ (json && json.message ? json.message : '유효하지 않은 코드입니다.') +'</div>';
                        if(hc) hc.value = '';
                        if(ic) ic.value = '';
                    }
                    try{ box.scrollIntoView({ behavior:'smooth', block:'center' }); }catch(e){}
                });
            } else {
                box.innerHTML = '<div style="font-size:13px; color:#2a7f62;">'+ msg +'</div>';
            }
        } else {
            box.innerHTML = '<div style="font-size:13px; color:#b00020;">추천인 코드가 없습니다.</div>';
        }
    }
}

</script>

<script>
var pageTypeParam = "pageType=register";
<?php if($config['cf_cert_use'] && $config['cf_cert_hp']) { 
    $cert_url = '';
    $cert_type = '';
    switch($config['cf_cert_hp']) {
        case 'kcb':
            $cert_url = G5_OKNAME_URL.'/hpcert1.php';
            $cert_type = 'kcb-hp';
            break;
        case 'kcp':
            $cert_url = G5_KCPCERT_URL.'/kcpcert_form.php';
            $cert_type = 'kcp-hp';
            break;
        case 'lg':
            $cert_url = G5_LGXPAY_URL.'/AuthOnlyReq.php';
            $cert_type = 'lg-hp';
            break;
    }
    echo "var CERT_HP_URL = '".$cert_url."'; var CERT_HP_TYPE = '".$cert_type."';";
} ?>
var IPIN_URL = "<?php echo G5_OKNAME_URL; ?>/ipin1.php";
var INI_URL = "<?php echo G5_INICERT_URL; ?>/ini_request.php";

document.addEventListener('DOMContentLoaded', function(){
    var btnHp = document.getElementById('win_hp_cert');
    if(btnHp){
        btnHp.addEventListener('click', function(){
            if(typeof cert_confirm === 'function' && !cert_confirm()) return;
            var params = '?' + pageTypeParam;
            if(typeof certify_win_open === 'function' && typeof CERT_HP_URL !== 'undefined' && CERT_HP_URL){
                certify_win_open(CERT_HP_TYPE, CERT_HP_URL + params);
            }
        });
    }
    var btnIpin = document.getElementById('win_ipin_cert');
    if(btnIpin){
        btnIpin.addEventListener('click', function(){
            if(typeof cert_confirm === 'function' && !cert_confirm()) return;
            var params = '?' + pageTypeParam;
            var url = IPIN_URL + params;
            if(typeof certify_win_open === 'function'){
                certify_win_open('kcb-ipin', url);
            }
        });
    }
    var saBtns = document.querySelectorAll('.win_sa_cert');
    for(var i=0;i<saBtns.length;i++){
        saBtns[i].addEventListener('click', function(){
            if(typeof cert_confirm === 'function' && !cert_confirm()) return;
            var type = this.getAttribute('data-type') || '';
            var request_url = INI_URL + '?directAgency=' + type + '&' + pageTypeParam;
            if(typeof call_sa === 'function'){
                call_sa(request_url);
            }
        });
    }
});
</script>

<!-- } 회원정보 입력/수정 끝 -->
