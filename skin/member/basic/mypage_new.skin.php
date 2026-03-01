<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css?v='.time().'">', 0);
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/css/referral.css">', 1);
include_once(G5_THEME_PATH.'/head.php');

// 추천인 기능 라이브러리 포함
include_once(G5_LIB_PATH.'/referral.lib.php');

// 수강현황 런칭쇼 수강신청 내역 확인
$sql = "
	SELECT COUNT(*) as cnt FROM g5_content_mypage_show WHERE user_no = ".$member['mb_no']."
";
$launchingCnt = sql_fetch($sql);

// 추천인 정보 가져오기
$referral_info = get_member_referral_info($member['mb_id']);
$referral_count = get_referral_count($member['mb_id']);
$referral_link = get_referral_link($member['mb_id']);
?>

<!-- 마이페이지 시작 { -->
<div id="mb_mypage" class="mypage subpage pbt100">
    <div class="inner_container">
		<!-- 추천인 섹션 추가 -->
		<div class="sect referral mb90">
			<h2 class="fs_42 fw_600">추천인 관리</h2>	
			<p class="fs_16 fw_400">추천인 정보와 추천 링크를 관리할 수 있습니다.</p>
			
			<div class="referral-section">
				<div class="referral-stats">
					<div class="stat-item">
						<span class="stat-label">총 추천인</span>
						<span class="stat-value"><?php echo number_format($referral_count); ?>명</span>
					</div>
					<div class="stat-item">
						<span class="stat-label">내 추천인</span>
						<span class="stat-value"><?php echo $referral_info ? $referral_info['mb_name'] . ' (' . $referral_info['mb_3'] . ')' : '없음'; ?></span>
					</div>
				</div>
				
				<div class="referral-actions">
					<button type="button" id="view_referrals" class="btn_referral">추천인 보기</button>
					<button type="button" id="copy_referral_link" class="btn_referral">추천링크복사</button>
				</div>
			</div>
		</div>
		
		<div class="sect privacy mb90">
        	<h2 class="fs_42 fw_600">개인 정보 수정</h2>
			<p class="fs_16 fw_400">회원님의 개인정보를 수정하실 수 있습니다.</p>
			<ul class="dfbox">
				<li><a href="<?=G5_BBS_URL?>/member_confirm.php?url=register_form.php" class="fs_16 fw_500">비밀번호 변경</a></li>
				<li><a href="<?=G5_BBS_URL?>/member_confirm.php?url=register_form.php" class="fs_16 fw_500">회원정보 변경</a></li>
				<li><a href="javascript:member_leave();" class="fs_16 fw_500">회원 탈퇴</a></li>
			</ul>
		</div>
		<div class="sect lecture mb90">
			<h2 class="fs_42 fw_600">수강 현황</h2>	
			<p class="fs_16 fw_400">회원님의 현재 학습중인 강좌를 확인 할 수 있습니다.</p>

<?php
	// 2025.03.12 런칭쇼 수강내역이 있는 경우, 단일 구성이 아닌 탭으로 구성.
	if ($launchingCnt['cnt'] > 0) {
?>

			<ul class="tabs">
				<li class="tab-link current" data-tab="tab-1">강의</li>
				<li class="tab-link" data-tab="tab-2">런칭쇼</li>
			</ul>

			<div id="tab-1" class="tab-content current">
				<div class="campus_wrap"><?php echo latest_lec('latest_basic_b', 'campus', 99, 60, $member['mb_no'], 'Y'); ?></div>
			</div>
			<div id="tab-2" class="tab-content">
				<div class="campus_wrap"><?php echo latest_lec('latest_basic_b', 'launchingShow', 99, 60, $member['mb_no'], 'Y'); ?></div>
			</div>

<?php
	} else {
?>
			<div class="campus_wrap"><?php echo latest_lec('latest_basic_b', 'campus', 99, 60, $member['mb_no']); ?></div> <!-- 스킨경로: /skin/latest/latest_basic_b/~~~ -->
<?php
	}
?>
		</div>
		
		<div class="sect lecture mb90">
			<h2 class="fs_42 fw_600">맞춤 영상</h2>	
			<p class="fs_16 fw_400">회원님이 즐겨찾기한 강좌를 확인 할 수 있습니다.</p>
			
			<!-- <ul class="tabs">
				<li class="tab-link current" data-tab="tab-3">강의</li>
				<li class="tab-link" data-tab="tab-4">런칭쇼</li>
			</ul> -->

			<div id="tab-3" class="tab-content current">
				<div class="campus_wrap"><?php echo latest_favorite('latest_basic_b', 'campus', 99, 60, $member['mb_no'], 'Y'); ?></div>
			</div>
			<!-- <div id="tab-4" class="tab-content">
				<div class="campus_wrap"></div>
			</div> -->
			
		</div>
		
		
		<div class="sect contact">
			<h2 class="fs_42 fw_600">문의 현황</h2>	
			<p class="fs_16 fw_400">회원님이 문의하신 내용을 확인 할 수 있습니다.</p>
			<!-- <iframe src="<?php echo G5_URL ?>/bbs/qalist" frameborder="0" scrolling="no" width="100%" id="iframe_board"></iframe> 게시판경로: /skin/qa/basic/~~~ -->

			<!-- [OSJ : 2024-03-13] 문의현황 변경 -->
			<? include_once(G5_PATH.'/bbs/qalist_inc.php');?>
		</div>
    </div>
</div>
<!-- } 마이페이지 끝 -->

<!-- 추천인 목록 모달 -->
<div id="referral_modal" class="referral-modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>추천인 목록</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="referral-list" id="referral_list">
                <div class="loading">로딩중...</div>
            </div>
        </div>
    </div>
</div>

<script>
function member_leave() {  // 회원 탈퇴 tto
	if (confirm("회원에서 탈퇴 하시겠습니까?"))
		location.href = '<?php echo G5_BBS_URL ?>/member_confirm.php?url=member_leave.php';
}

$(document).ready(function(){
	// 기존 탭 기능
	$('ul.tabs li').click(function(){
		var tab_id = $(this).attr('data-tab');

		$('ul.tabs li').removeClass('current');
		$('.tab-content').removeClass('current');

		$(this).addClass('current');
		$("#"+tab_id).addClass('current');
	});

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
	
	// 추천인 기능
	$('#view_referrals').click(function() {
		loadReferrals();
		$('#referral_modal').show();
	});
	
	$('#copy_referral_link').click(function() {
		copyReferralLink();
	});
	
	$('.modal-close').click(function() {
		$('#referral_modal').hide();
	});
	
	// 모달 외부 클릭 시 닫기
	$(window).click(function(e) {
		if (e.target.id === 'referral_modal') {
			$('#referral_modal').hide();
		}
	});
});

// 추천인 목록 로드
function loadReferrals() {
	$.ajax({
		url: '/bbs/ajax.referral.php',
		type: 'POST',
		dataType: 'json',
		data: {
			action: 'get_referral_count',
			mb_id: '<?php echo $member['mb_id']; ?>'
		},
		success: function(response) {
			if (response.success) {
				displayReferrals(response.referrals);
			} else {
				$('#referral_list').html('<div class="no-data">추천인이 없습니다.</div>');
			}
		},
		error: function() {
			$('#referral_list').html('<div class="error">데이터 로드 중 오류가 발생했습니다.</div>');
		}
	});
}

// 추천인 목록 표시
function displayReferrals(referrals) {
	var html = '';
	
	if (referrals.length === 0) {
		html = '<div class="no-data">추천인이 없습니다.</div>';
	} else {
		html = '<table class="referral-table">';
		html += '<thead><tr><th>이름</th><th>치과명/학교명</th><th>가입일</th></tr></thead>';
		html += '<tbody>';
		
		for (var i = 0; i < referrals.length; i++) {
			var referral = referrals[i];
			var date = new Date(referral.mb_datetime);
			var formattedDate = date.getFullYear() + '-' + 
				String(date.getMonth() + 1).padStart(2, '0') + '-' + 
				String(date.getDate()).padStart(2, '0');
			
			html += '<tr>';
			html += '<td>' + referral.mb_name + '</td>';
			html += '<td>' + referral.mb_3 + '</td>';
			html += '<td>' + formattedDate + '</td>';
			html += '</tr>';
		}
		
		html += '</tbody></table>';
	}
	
	$('#referral_list').html(html);
}

// 추천인 링크 복사
function copyReferralLink() {
	var referralLink = '<?php echo $referral_link; ?>';
	
	// 클립보드에 복사
	if (navigator.clipboard) {
		navigator.clipboard.writeText(referralLink).then(function() {
			alert('추천인 링크가 복사되었습니다.');
		}).catch(function() {
			fallbackCopyTextToClipboard(referralLink);
		});
	} else {
		fallbackCopyTextToClipboard(referralLink);
	}
}

// 구형 브라우저용 클립보드 복사
function fallbackCopyTextToClipboard(text) {
	var textArea = document.createElement("textarea");
	textArea.value = text;
	document.body.appendChild(textArea);
	textArea.focus();
	textArea.select();
	
	try {
		var successful = document.execCommand('copy');
		if (successful) {
			alert('추천인 링크가 복사되었습니다.');
		} else {
			alert('링크 복사에 실패했습니다.');
		}
	} catch (err) {
		alert('링크 복사에 실패했습니다.');
	}
	
	document.body.removeChild(textArea);
}
</script>

<?php
include_once(G5_THEME_PATH.'/tail.php');
?>