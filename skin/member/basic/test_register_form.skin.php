<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
add_javascript('<script src="'.G5_JS_URL.'/jquery.register_form.js"></script>', 0);
if ($config['cf_cert_use'] && ($config['cf_cert_simple'] || $config['cf_cert_ipin'] || $config['cf_cert_hp']))
    add_javascript('<script src="'.G5_JS_URL.'/certify.js?v='.G5_JS_VER.'"></script>', 0);

// 테스트 모드 추가
$test_mode = true;
?>
<style>
.referral-grid { display:grid; grid-template-columns: repeat(2, 1fr); gap:12px; }
@media (max-width: 640px) {
  .referral_search_input { flex-direction: column; gap: 8px; align-items: stretch !important; }
  #search_type { width: 100% !important; min-width: 0 !important; }
  #btn_referral_search { width: 100% !important; min-width: 0 !important; }
  .referral-grid { grid-template-columns: 1fr !important; }
}
</style>

<!-- 회원정보 입력/수정 시작 { -->

<div class="register subpage pbt100">
<div class="inner_container">
	<form id="fregisterform" name="fregisterform" action="<?php echo $action_url ?>" onsubmit="return fregisterform_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="w" value="<?php echo $w ?>">
	<input type="hidden" name="url" value="<?php echo $urlencode ?>">
	<input type="hidden" name="agree" value="<?php echo $agree ?>">
	<input type="hidden" name="agree2" value="<?php echo $agree2 ?>">
	<input type="hidden" name="cert_type" value="<?php echo $member['mb_certify']; ?>">
	<input type="hidden" name="cert_no" value="">
	<input type="hidden" name="test_mode" value="1"> <!-- 테스트 모드 플래그 -->
	<?php if (isset($member['mb_sex'])) {  ?><input type="hidden" name="mb_sex" value="<?php echo $member['mb_sex'] ?>"><?php }  ?>
	<?php if (isset($referral_code_param) && $referral_code_param) {  ?><input type="hidden" name="referral_code" value="<?php echo $referral_code_param ?>"><?php }  ?>
	<div class="logo_title">
		<div class="imgbox"><img src="../<?php echo G5_SKIN_DIR ?>/member/basic/img/register_logo.png" alt="logo"></div>
		<h2 class="fs_44 fw_500"><?php echo $g5['title'] ?></h2>
	</div>
		<div class="register_left">
			<div id="register_form" class="form_01">   
				<div class="register_form_inner">
	        <ul>
	            <li class="id_auth">
					<div class="dfbox id_wrap ais">
	                	<label for="reg_mb_id">아이디</label>
						<div class="input_btn dfbox">
							<input type="text" name="mb_id" value="<?php echo $member['mb_id'] ?>" id="reg_mb_id" <?php echo $required ?> <?php echo $readonly ?> class="frm_input full_input <?php echo $required ?> <?php echo $readonly ?>" minlength="3" maxlength="50" placeholder="이메일 주소를 입력하세요. 아이디는 이메일 주소로 사용됩니다." style="width:100%;">

						</div>
					</div>
	            </li>

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
          				 </div>
                </li>
                
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
	    <div class="tbl_frm01 tbl_wrap register_form_inner">
	        <h2>자동등록방지</h2>
	        <ul>
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
	            
	            <li class="is_captcha_use">
	                <!-- 자동등록방지 -->
	                <?php echo captcha_html(); ?>
	            </li>
	        </ul>
	    </div>
	    </div>
	</div>
	
	<div class="register_right">
		<!-- 가입 요약 섹션 - 숨김 처리 -->
		<div class="register_summary sticky_summary" style="display: none;">
			<h2>가입 요약</h2>
			<div class="summary_content">
				<p>회원가입을 환영합니다!</p>
				<ul class="summary_list">
					<li><span class="summary_label">아이디:</span> <span id="summary_id" class="summary_val">-</span></li>
					<li><span class="summary_label">이름:</span> <span id="summary_name" class="summary_val">-</span></li>
					<li><span class="summary_label">이메일:</span> <span id="summary_email" class="summary_val">-</span></li>
				</ul>
			</div>
		</div>
		
		<!-- 추천인 기능 추가 - 탭 기반 UI -->
        <div class="referral_section" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 15px; display: block !important; visibility: visible !important; border: 2px solid #007bff !important;">
            <h4 style="margin-bottom: 10px; color: #495057;">추천인 찾기</h4>
			
			<!-- 탭 버튼 -->
			<div class="referral_tabs" style="margin-bottom: 15px;">
				<button type="button" class="referral_tab_btn active" data-tab="search" style="padding: 8px 15px; margin-right: 5px; border: 1px solid #ddd; background: #fff; cursor: pointer; border-radius: 3px;">추천인 검색하기</button>
				<button type="button" class="referral_tab_btn" data-tab="code" style="padding: 8px 15px; border: 1px solid #ddd; background: #f8f9fa; cursor: pointer; border-radius: 3px;">추천코드 입력하기</button>
			</div>
			
			<!-- 추천인 검색 탭 -->
			<div class="referral_tab_content" id="referral_search_tab" style="display: block;">
				<div class="referral_search_input" style="margin-bottom: 10px; display: flex; gap: 5px; align-items: center;">
					<select name="search_type" id="search_type" class="frm_input" style="width: 120px; min-width: 120px;">
						<option value="name">이름검색</option>
						<option value="clinic">병원/소속 검색</option>
					</select>
					<input type="text" name="search_keyword" id="search_keyword" class="frm_input" placeholder="추천인 이름을 입력하세요" style="flex: 1; margin-bottom: 0;">
					<button type="button" id="btn_referral_search" class="btn_frmline" style="width: 80px; min-width: 80px; white-space: nowrap;">검색</button>
				</div>
				<div id="referral_search_result" style="font-size: 12px; color: #666;"></div>
			</div>
			
			<!-- 추천인 코드 입력 탭 -->
			<div class="referral_tab_content" id="referral_code_tab" style="display: none;">
				<div class="referral_code_input" style="margin-bottom: 10px;">
                    <input type="text" name="mb_referral_code" id="reg_mb_referral_code" class="frm_input" placeholder="추천인 코드를 입력하세요" style="width: 100%; margin-bottom: 5px;">
					<button type="button" id="btn_referral_check" class="btn_frmline" style="width: 100%;">추천인 확인</button>
				</div>
				<div id="referral_code_result" style="font-size: 12px; color: #666;"></div>
			</div>
			
			<!-- 숨겨진 필드 - 최종 선택된 추천인 ID -->
			<input type="hidden" name="mb_referred_by" id="reg_mb_referred_by" value="">
		</div>

        <div class="btn_confirm_right" style="display: block !important; visibility: visible !important;">
            <button type="submit" id="btn_submit" class="btn_frmline" accesskey="s" style="width: 100%;"><?php echo $w==''?'테스트 회원가입 신청':'정보수정'; ?></button>
			<!-- <a href="<?php echo G5_URL ?>" class="btn_close">취소</a> -->
		</div>
	</div>
	</div>

	<script>
	// Simple script to update summary as user types
	$(function() {
		console.log('테스트 회원가입 폼 로드됨');
		console.log('jQuery 버전:', $.fn.jquery);
		console.log('추천인 섹션 존재:', $('.referral_section').length > 0);
		console.log('제출 버튼 존재:', $('#btn_submit').length > 0);
		$('#reg_mb_id').on('input', function() {
			$('#summary_id').text($(this).val() || '-');
			$('#summary_email').text($(this).val() || '-'); // In this skin, ID is Email
		});

		$(document).on('click', '.referral-page-link', function(e) {
			e.preventDefault();
			var p = parseInt($(this).data('page'), 10);
			if (p && p > 0) {
				doReferralSearch(p);
			}
		});
		$('#reg_mb_name').on('input', function() {
			$('#summary_name').text($(this).val() || '-');
		});
		
		// 검색 타입 변경 시 플레이스홀더 업데이트
		$('#search_type').change(function() {
			var searchType = $(this).val();
			var placeholder = searchType === 'name' ? '추천인 이름을 입력하세요' : '소속(치과명)을 입력하세요';
			$('#search_keyword').attr('placeholder', placeholder);
			$('#search_keyword').val(''); // 검색어 초기화
			$('#referral_search_result').html(''); // 결과 초기화
		});
		
		// 추천인 탭 스위칭 기능
		$('.referral_tab_btn').click(function() {
			var tab = $(this).data('tab');
			
			// 탭 버튼 스타일 변경
			$('.referral_tab_btn').css('background', '#f8f9fa');
			$(this).css('background', '#fff');
			
			// 탭 내용 표시/숨김
			$('.referral_tab_content').hide();
			$('#referral_' + tab + '_tab').show();
			
			// 결과 메시지 초기화
			$('#referral_search_result').html('');
			$('#referral_code_result').html('');
		});
		
		function buildSearchData(page) {
			var searchType = $('#search_type').val();
			var keyword = $('#search_keyword').val().trim();
			if (!keyword || keyword.length < 2) {
				alert('검색어를 2글자 이상 입력해주세요.');
				$('#search_keyword').focus();
				return null;
			}
			var sd = { test_mode: 0, page: page || 1, page_size: 20 };
			if (searchType === 'name') { sd.name = keyword; } else { sd.clinic = keyword; }
			return sd;
		}

		function doReferralSearch(page) {
			var searchData = buildSearchData(page);
			if (!searchData) return;
			$.ajax({
				url: "<?php echo G5_BBS_URL ?>/ajax.referral_search.php",
				type: "POST",
				data: searchData,
				success: function(response) {
					var data = JSON.parse(response);
					if (data.success) {
						if (data.html) {
                            var gridStart = '<div class="referral-grid">';
							var gridEnd = '</div>';
							var pagination = '';
							if (data.total_pages && data.total_pages > 1) {
								var prevDisabled = (data.page <= 1) ? 'opacity:0.5;pointer-events:none;' : '';
								var nextDisabled = (data.page >= data.total_pages) ? 'opacity:0.5;pointer-events:none;' : '';
								pagination += '<div class="referral-pagination" style="margin-top:10px;display:flex;gap:6px;align-items:center;justify-content:center;">';
								pagination += '<a href="#" class="referral-page-link" data-page="' + (data.page - 1) + '" style="padding:4px 8px;border:1px solid #ddd;border-radius:3px;' + prevDisabled + '">이전</a>';
								pagination += '<span style="padding:4px 8px;">' + data.page + ' / ' + data.total_pages + '</span>';
								pagination += '<a href="#" class="referral-page-link" data-page="' + (data.page + 1) + '" style="padding:4px 8px;border:1px solid #ddd;border-radius:3px;' + nextDisabled + '">다음</a>';
								pagination += '</div>';
							}
                            $('#referral_search_result').html('<div style="margin-top: 10px; text-align: center;"><p style="color: #28a745; margin-bottom: 10px; font-size: 15px; line-height:0.8; letter-spacing:0.5px;">✓ ' + data.message + '</p>' + gridStart + data.html + gridEnd + pagination + '</div>');
						} else if (data.member) {
							var html = '<div style="margin-top: 10px;">';
							html += '<p style="color: #28a745; margin-bottom: 10px;">✓ 추천인을 찾았습니다:</p>';
                            html += '<div style="border: 1px solid #ddd; padding: 12px; border-radius: 3px; background: #fff; text-align: center; font-size:15px; line-height:1.8; letter-spacing:0.5px;">';
                            html += '<div style="font-weight:700; margin-bottom:4px;">' + data.member.mb_name + ' (' + data.member.mb_id + ')</div>';
                            html += '<div style="margin-bottom:4px;">치과명: ' + data.member.mb_3 + '</div>';
                            html += '<button type="button" onclick="selectReferrer(\'' + data.member.mb_id + '\', \'' + data.member.mb_name + '\')" style="margin-top: 4px; padding: 6px 12px; font-size: 15px; display: inline-block;" class="btn_frmline">선택</button>';
							html += '</div>';
							html += '</div>';
							$('#referral_search_result').html(html);
						}
					} else {
						$('#referral_search_result').html('<span style="color: #dc3545;">✗ ' + data.message + '</span>');
					}
				},
				error: function() {
					$('#referral_search_result').html('<span style="color: #dc3545;">✗ 추천인 검색 중 오류가 발생했습니다.</span>');
				}
			});
		}

		$('#btn_referral_search').click(function() {
			doReferralSearch(1);
		});
		
		// 추천인 코드 확인 기능
		$('#btn_referral_check').click(function() {
			var referral_code = $('#reg_mb_referral_code').val();
			if (!referral_code) {
				alert('추천인 코드를 입력해주세요.');
				return;
			}
			
			$.ajax({
				url: "<?php echo G5_BBS_URL ?>/ajax.referral.php",
				type: "POST",
				data: {
					referral_code: referral_code,
					test_mode: 0
				},
				success: function(response) {
					var data = JSON.parse(response);
					if (data.success) {
						$('#referral_code_result').html('<span style="color: #28a745;">✓ ' + data.message + '</span>');
						// 추천인 ID 저장
						if (data.member_id) {
							$('#reg_mb_referred_by').val(data.member_id);
						}
					} else {
						$('#referral_code_result').html('<span style="color: #dc3545;">✗ ' + data.message + '</span>');
					}
				},
				error: function() {
					$('#referral_code_result').html('<span style="color: #dc3545;">✗ 추천인 확인 중 오류가 발생했습니다.</span>');
				}
			});
		});
		
		// 추천인 선택 함수
		window.selectReferrer = function(mb_id, mb_name) {
			$('#reg_mb_referred_by').val(mb_id);
			alert(mb_name + '님을 추천인으로 선택했습니다.');
			// 검색 결과 초기화
			$('#referral_search_result').html('<span style="color: #28a745;">✓ 선택된 추천인: ' + mb_name + '</span>');
		};
		
		// 추천인 코드 자동 입력 (URL 파라미터로 전달된 경우)
		var urlParams = new URLSearchParams(window.location.search);
		var refCode = urlParams.get('ref');
		if (refCode) {
			$('#reg_mb_referral_code').val(refCode);
			$('#btn_referral_check').click();
		}
	});
	</script>
	
	<div class="btn_confirm" style="display:none;">
	    <!-- <a href="<?php echo G5_URL ?>" class="btn_close">취소</a> -->
	    <button type="submit" id="btn_submit_hidden" class="btn_submit" accesskey="s"><?php echo $w==''?'테스트 회원가입 신청':'정보수정'; ?></button>
	</div>
	</form>
	</div>
</div>

<script>
$(function() {
    $("#reg_zip_find").css("display", "inline-block");
    var pageTypeParam = "pageType=register";
});

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
    }

    // 휴대폰번호 체크
    var msg = reg_mb_hp_check();
    if (msg) {
        alert(msg);
        f.reg_mb_hp.select();
        return false;
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

    // 버튼 비활성화 대신 로딩 표시
    var submitBtn = document.getElementById("btn_submit");
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '처리중...';
    }

    return true;
}
</script>

<!-- } 회원정보 입력/수정 끝 -->
