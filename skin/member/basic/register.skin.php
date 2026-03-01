<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
?>

<!-- 회원가입약관 동의 시작 { -->
<div class="register subpage pbt100">
<div class="inner_container">

    <form  name="fregister" id="fregister" action="<?php echo $register_action_url ?>" onsubmit="return fregister_submit(this);" method="POST" autocomplete="off">
    <input type="hidden" name="mode" value="<?php echo $_GET['mode'];?>" >
    <?php $ref_init = ''; if (isset($_GET['referral_code']) && $_GET['referral_code']) { $ref_init = trim($_GET['referral_code']); } else if (function_exists('get_session')) { $ref_sess = get_session('referral_code'); if ($ref_sess) $ref_init = $ref_sess; } ?>
    <input type="hidden" name="referral_code" value="<?php echo htmlspecialchars($ref_init); ?>">
    <p><i class="fa fa-check-circle" aria-hidden="true"></i> 회원가입약관 및 개인정보 수집 및 이용의 내용에 동의하셔야 회원가입 하실 수 있습니다.</p>
    
    <?php
    // 소셜로그인 사용시 소셜로그인 버튼
    @include_once(get_social_skin_path().'/social_register.skin.php');
    ?>
    <section id="fregister_term">
        <h2>회원가입약관</h2>
        <textarea readonly><?php echo get_text($config['cf_stipulation']) ?></textarea>
        <fieldset class="fregister_agree">
            <input type="checkbox" name="agree" value="1" id="agree11" class="selec_chk">
            <label for="agree11"><span></span><b class="sound_only">회원가입약관의 내용에 동의합니다.</b></label>
        </fieldset>
    </section>

    <section id="fregister_private">
        <h2>개인정보 수집 및 이용</h2>
        <div>
            <table>
                <caption>개인정보 수집 및 이용</caption>
                <thead>
                <tr>
                    <th>목적</th>
                    <th>항목</th>
                    <th>보유기간</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>이용자 식별 및 본인여부 확인</td>
                    <td>아이디, 이름, 비밀번호<?php echo ($config['cf_cert_use'])? ", 생년월일, 휴대폰 번호(본인인증 할 때만, 아이핀 제외), 암호화된 개인식별부호(CI)" : ""; ?></td>
                    <td>회원 탈퇴 시까지</td>
                </tr>
                <tr>
                    <td>고객서비스 이용에 관한 통지,<br>CS대응을 위한 이용자 식별</td>
                    <td>연락처 (이메일, 휴대전화번호)</td>
                    <td>회원 탈퇴 시까지</td>
                </tr>
				<tr>
                    <td>Straumann이 제공하는 이용자 맞춤형<br> 서비스 및 상품 추천 각종 경품 행사,이벤트 등의 광고성 정보를 전자우편이나 서신우편,문자(SMS 또는 카카오 알림톡),푸시,전화 등을 통해 이용자에게 제공합니다.</td>
                    <td>이름,이메일주소,휴대전화번호,<br>마케팅수신동의 여부</td>
                    <td>회원탈퇴 후 30일 또는 동의 철회시까지</td>
                </tr>
                </tbody>
            </table>
        </div>

        <fieldset class="fregister_agree">
            <input type="checkbox" name="agree2" value="1" id="agree21" class="selec_chk">
            <label for="agree21"><span></span><b class="sound_only">개인정보 수집 및 이용의 내용에 동의합니다.</b></label>
       </fieldset>
    </section>
	
	<div id="fregister_chkall" class="chk_all fregister_agree">
        <input type="checkbox" name="chk_all" id="chk_all" class="selec_chk">
        <label for="chk_all"><span></span>회원가입 약관에 모두 동의합니다</label>
    </div>
	    
    <div class="btn_confirm">
    	<!-- <a href="<?php echo G5_URL ?>" class="btn_close">취소</a> -->
      <button type="submit" class="btn_submit">회원가입 신청</button>
    </div>

    </form>
    </div>

    <script>
    function fregister_submit(f)
    {
        if (!f.agree.checked) {
            alert("회원가입약관의 내용에 동의하셔야 회원가입 하실 수 있습니다.");
            f.agree.focus();
            return false;
        }

        if (!f.agree2.checked) {
            alert("개인정보 수집 및 이용의 내용에 동의하셔야 회원가입 하실 수 있습니다.");
            f.agree2.focus();
            return false;
        }

        return true;
    }
    
    jQuery(function($){
        // 모두선택
        $("input[name=chk_all]").click(function() {
            if ($(this).prop('checked')) {
                $("input[name^=agree]").prop('checked', true);
            } else {
                $("input[name^=agree]").prop("checked", false);
            }
        });
    });

    </script>
    <script>
    (function(){
        document.addEventListener('DOMContentLoaded', function(){
            try {
                var url = new URL(window.location.href);
                var ref = url.searchParams.get('referral_code') || url.searchParams.get('ref');
                var hid = document.querySelector('input[name="referral_code"]');
                if(ref){
                    try { sessionStorage.setItem('referral_code', ref); localStorage.setItem('referral_code', ref); document.cookie = 'referral_code='+encodeURIComponent(ref)+'; path=/; max-age='+(60*60*24*7); } catch(e){}
                    if(hid) hid.value = ref;
                } else if(hid && !hid.value){
                    var saved = null;
                    try { saved = sessionStorage.getItem('referral_code') || localStorage.getItem('referral_code'); if(!saved){ var m = document.cookie.match(/(?:^|; )referral_code=([^;]+)/); if(m && m[1]) saved = decodeURIComponent(m[1]); } } catch(e){}
                    if(saved){ hid.value = saved; }
                }
            } catch(e){}
        });
    })();
    </script>
</div>
<!-- } 회원가입 약관 동의 끝 -->
