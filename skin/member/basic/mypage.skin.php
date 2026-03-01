<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css?v='.time().'">', 0);
include_once(G5_THEME_PATH.'/head.php');

include_once(G5_LIB_PATH.'/referral_new.lib.php');
$referral_code = get_or_create_referral_code($member['mb_id']);
$referral_link = get_referral_link($member['mb_id']);

// 수강현황 런칭쇼 수강신청 내역 확인
$sql = "
	SELECT COUNT(*) as cnt FROM g5_content_mypage_show WHERE user_no = ".$member['mb_no']."
";
$launchingCnt = sql_fetch($sql);
?>

<!-- 마이페이지 시작 { -->
<div id="mb_mypage" class="mypage subpage pbt100">
    <div class="inner_container">
		<div class="sect privacy mb90">
			<h2 class="fs_42 fw_600">개인 정보 수정</h2>
			<p class="fs_16 fw_400">회원님의 개인정보를 수정하실 수 있습니다.</p>
			<ul class="dfbox">
				<li><a href="<?=G5_BBS_URL?>/member_confirm.php?url=register_form.php" class="fs_16 fw_500">비밀번호 변경</a></li>
				<li><a href="<?=G5_BBS_URL?>/member_confirm.php?url=register_form.php" class="fs_16 fw_500">회원정보 변경</a></li>
				<li><a href="javascript:member_leave();" class="fs_16 fw_500">회원 탈퇴</a></li>
			</ul>
			<div style="margin-top:10px;">
				<button type="button" id="btn_open_referral" style="width:490px; min-height:40px; font-size:14px; border:2px solid #2a7f62; color:#2a7f62; background:#fff; border-radius:0;">회원 가입 추천하기</button>
			</div>
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
		
		
		<?php $__stats = get_referral_statistics_new($member['mb_id']); $__refs = isset($__stats['referrals']) ? $__stats['referrals'] : array(); function __mask_name($__n){ $__n = trim((string)$__n); if($__n==='') return ''; $__len = function_exists('mb_strlen') ? mb_strlen($__n,'UTF-8') : strlen($__n); if($__len<=1) return $__n.'*'; $__first = function_exists('mb_substr') ? mb_substr($__n,0,1,'UTF-8') : substr($__n,0,1); $__last = function_exists('mb_substr') ? mb_substr($__n,$__len-1,1,'UTF-8') : substr($__n,$__len-1,1); if($__len==2) return $__first.'*'; return $__first.str_repeat('*',$__len-2).$__last; } ?>
		<div class="sect contact">
			<h2 class="fs_42 fw_600">추천 내역</h2>
			<p class="fs_16 fw_400">회원님의 추천으로 가입한 내역입니다. 이름은 일부만 표시됩니다.</p>
			<?php if ($__refs && is_array($__refs)) { ?>
			<table class="grid-table" style="width:100%">
				<thead>
					<tr>
                        <th style="width:80px">번호</th>
                        <th>이름</th>
                        <th>소속</th>
						<th style="width:120px">가입날짜</th>
					</tr>
				</thead>
				<tbody>
					<?php for($i=0;$i<count($__refs);$i++){ $r = $__refs[$i]; $nm = __mask_name(isset($r['mb_name'])?$r['mb_name']:''); $aff = isset($r['mb_3'])?$r['mb_3']:''; $no = $i+1; $dt = isset($r['mb_datetime'])?$r['mb_datetime']:''; $dt_display = $dt ? date('Y.m.d', strtotime($dt)) : ''; ?>
					<tr>
                        <td style="font-weight:600; color:#2a7f62;"><?php echo $no; ?></td>
						<td><?php echo htmlspecialchars($nm); ?></td>
						<td><?php echo htmlspecialchars($aff); ?></td>
						<td><?php echo htmlspecialchars($dt_display); ?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php } else { ?>
			<p class="muted">추천 내역이 없습니다.</p>
			<?php } ?>
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


<script>
function member_leave(){
    if(confirm("회원에서 탈퇴 하시겠습니까?")) location.href = '<?php echo G5_BBS_URL ?>/member_confirm.php?url=member_leave.php';
}
document.addEventListener('DOMContentLoaded', function(){
    var tabs = document.querySelectorAll('ul.tabs li');
    for(var i=0;i<tabs.length;i++){
        tabs[i].addEventListener('click', function(){
            var tab_id = this.getAttribute('data-tab');
            var allTabs = document.querySelectorAll('ul.tabs li');
            for(var j=0;j<allTabs.length;j++){ allTabs[j].classList.remove('current'); }
            var contents = document.querySelectorAll('.tab-content');
            for(var k=0;k<contents.length;k++){ contents[k].classList.remove('current'); }
            this.classList.add('current');
            var target = document.getElementById(tab_id);
            if(target) target.classList.add('current');
        });
    }
    var iframe = document.getElementById('iframe_board');
    if(iframe){
        iframe.addEventListener('load', function(){
            var doc = iframe.contentDocument || iframe.contentWindow.document;
            if(!doc) return;
            var hideSelectors = ['#hd', '#ft', 'footer', '.comm_snsWrap', '.board_header_wrap'];
            for(var h=0; h<hideSelectors.length; h++){
                var nodes = doc.querySelectorAll(hideSelectors[h]);
                for(var n=0;n<nodes.length;n++){ nodes[n].style.display = 'none'; }
            }
            var qa = doc.querySelector('.subpage.qa');
            if(qa){ qa.style.padding = '0'; }
            if(doc.body){ iframe.style.height = (doc.body.scrollHeight + 30) + 'px'; }
            var links = doc.querySelectorAll('.bo_tit a');
            for(var l=0;l<links.length;l++){
                links[l].addEventListener('click', function(){
                    setInterval(function(){ if(doc && doc.body){ iframe.style.height = (doc.body.scrollHeight + 30) + 'px'; } }, 300);
                });
            }
        });
    }
    var _openBtn = document.getElementById('btn_open_referral');
    var _modal = document.getElementById('referral_modal');
    var _closeBtn = document.getElementById('btn_close_referral_modal');
    var _copyBtn = document.getElementById('btn_copy_link_modal');
    var _shareBtn = document.getElementById('btn_share_kakao_modal');
    var _linkInput = document.getElementById('referral_link_input_modal');
    if(_openBtn && _modal){ _openBtn.addEventListener('click', function(){ _modal.style.display = 'flex'; }); }
    if(_closeBtn && _modal){ _closeBtn.addEventListener('click', function(){ _modal.style.display = 'none'; }); }
    if(_modal){ _modal.addEventListener('click', function(e){ if(e.target === _modal){ _modal.style.display = 'none'; } }); }
    if(_copyBtn && _linkInput){
        _copyBtn.addEventListener('click', function(){
            var v = _linkInput.value || '';
            if(navigator.clipboard && navigator.clipboard.writeText){ navigator.clipboard.writeText(v).then(function(){ alert('추천 링크가 복사되었습니다.'); }); }
            else { var tmp = document.createElement('textarea'); tmp.value = v; document.body.appendChild(tmp); tmp.select(); document.execCommand('copy'); document.body.removeChild(tmp); alert('추천 링크가 복사되었습니다.'); }
        });
    }
    if(_shareBtn && _linkInput){
        _shareBtn.addEventListener('click', function(){
            var v = _linkInput.value || '';
            var img = 'https://stkr-edu.com/theme/basic/img/main/logo_r.png';
            if(window.kakaolink_send){ kakaolink_send('스트라우만 캠퍼스 추천', v, img); }
            else if(window.Kakao && typeof Kakao === 'object'){
                try{ if(!Kakao.isInitialized() && typeof kakao_javascript_apikey !== 'undefined' && kakao_javascript_apikey){ Kakao.init(kakao_javascript_apikey); } }catch(e){}
                Kakao.Link.sendDefault({ objectType:'feed', content:{ title:'스트라우만 캠퍼스 추천', description:v, imageUrl:img, link:{ mobileWebUrl:v, webUrl:v } }, buttons:[{ title:'회원가입하기', link:{ mobileWebUrl:v, webUrl:v } }] });
            }
            else { alert('카카오톡 공유를 사용할 수 없습니다.'); }
        });
    }
});
</script>

<div id="referral_modal" style="display:none; position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(0,0,0,0.45); align-items:center; justify-content:center; z-index:9999;">
  <div style="width:90%; max-width:520px; background:#ffffff; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.25); padding:24px; text-align:center; font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">
    <h3 style="margin:0 0 8px 0; font-size:20px; color:#1f2937;">회원 가입 추천하기</h3>
    <p style="margin:0 0 14px 0; font-size:14px; color:#374151;">아래 추천 링크를 복사하거나 카카오톡으로 공유하세요.</p>
    <div style="margin-bottom:8px; font-size:15px;">추천인 코드: <span style="color:#2a7f62; font-weight:600;"><?= htmlspecialchars($referral_code ? $referral_code : '') ?></span></div>
    <div style="display:flex; gap:30px; align-items:center; margin-top:8px;">
      <input type="text" id="referral_link_input_modal" value="<?= htmlspecialchars($referral_link ? $referral_link : '') ?>" readonly style="flex:1; height:38px; padding:8px 12px; border:1px solid #cfe5dc; border-radius:8px; background:#fff; color:#0f3060; font-size:14px;" />
    </div>
    <div style="display:flex; gap:30px; justify-content:center; margin-top:12px;">
      <button type="button" id="btn_copy_link_modal" style="width:75px; border:2px solid #2a7f62; color:#2a7f62; background:#fff; border-radius:0; height:40px;">복사</button>
      <button type="button" id="btn_share_kakao_modal" style="width:120px; border:2px solid #ffeb00; color:#1a1a1a; background:#ffeb00; border-radius:0; height:40px;">카카오톡으로 공유</button>
      <button type="button" id="btn_close_referral_modal" style="width:75px; border:1px solid #ddd; color:#333; background:#fff; border-radius:0; height:40px;">닫기</button>
    </div>
  </div>
</div>

<script src="https://developers.kakao.com/sdk/js/kakao.min.js"></script>
<script>
var kakao_javascript_apikey = "<?= isset($config['cf_kakao_js_apikey']) ? $config['cf_kakao_js_apikey'] : '' ?>";
if(!kakao_javascript_apikey){ kakao_javascript_apikey = "cf8cf9310d88755dca5d8d48163f661f"; }
</script>
<script src="/js/kakaolink.js"></script>

<?php
include_once(G5_THEME_PATH.'/tail.php');
?>
