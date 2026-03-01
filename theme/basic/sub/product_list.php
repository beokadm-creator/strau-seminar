<?php
include_once('../../../common.php');

$g5['title'] = "제품정보";
$g5['title2'] = "proList";
$depth1 = 2;

if ($member['mb_level'] < 3) {
		if ($is_member)
				alert('글을 읽을 권한이 없습니다.', G5_URL);
		else
				goto_url(G5_BBS_URL.'/login.php?wr_id='.$wr_id.$qstr.'&amp;url='.urlencode(get_pretty_url($bo_table, $wr_id, $qstr)));
}

include_once(G5_THEME_PATH.'/head.php');
?>

<!--컨텐츠시작-->
<div class="<? echo $g5['title2']?>_wrap pbt100">
	<div class="inner_container">
		<div class="common_wrap mb65">
			<h2 class="tit fs_42 fw_700"><? echo $g5['title'];?></h2>	
		</div>
		<ul class="proList_cont dfbox">
				<li>
					<a href="<?= G5_URL; ?>/sub/list.php?cateno=104101&cate1=104&cate2=104101">
						<div class="imgbox">
							<img src="<?= G5_THEME_IMG_URL; ?>/sub/proList_img1.jpg" alt="img">
							<p>스트라우만 임플란트</p>
						</div>
						<div class="txtbox">스트라우만 임플란트는 70년 이상의 역사를 가진 임플란트 업계의 대표 기업으로서 최고의 친수성 표면처리와 차별화된 록솔리드 재질로 환자에게 예측가능한 안정적인 임상결과를 제공합니다.</div>
					</a>
				</li>
				<li>
					<a href="<?= G5_URL; ?>/sub/list.php?cateno=104102&cate1=104&cate2=104102">
						<div class="imgbox">
							<img src="<?= G5_THEME_IMG_URL; ?>/sub/proList_img2.jpg" alt="img">
							<p>앤서지 임플란트</p>
						</div>
						<div class="txtbox">앤서지는 혁신적인 임플란트와 보철 솔루션을 제공하는 프랑스 기업으로 70년이 넘는 고정밀 의료기기 및 치과 기기 분야의 노하우를 가지고 있습니다. 보험적용이 가능한 앤서지 임플란트로 차별화된 진료를 환자에게 제공하고 만족도를 높일 수 있습니다.</div>
					</a>
				</li>
				<li>
					<a href="<?= G5_URL; ?>/sub/list.php?cateno=105&cate1=105">
						<div class="imgbox">
							<img src="<?= G5_THEME_IMG_URL; ?>/sub/proList_img3.jpg" alt="img">
							<p>바이오머테리얼</p>
						</div>
						<div class="txtbox">치주조직, 치조골의 예측가능한 재생을 촉진할 수 있는 엠도게인과 안정적인 골이식재, 멤브레인을 통해 환자에게 차별화된 임상결과를 제공합니다.</div>
					</a>
				</li>
				<li>
					<a href="<?= G5_URL; ?>/sub/list.php?cateno=106&cate1=106">
						<div class="imgbox">
							<img src="<?= G5_THEME_IMG_URL; ?>/sub/proList_img4.jpg" alt="img">
							<p>디지털 솔루션</p>
						</div>
						<div class="txtbox">스트라우만 디지털 솔루션은 치과 의사, 기공사, 그리고 환자 모두를 위한 서비스를 제공합니다. 구강 스캐너, 밀링, 3D 프린팅 등의 혁신적인 제품을 통해 치과 진료 워크플로우를 디지털화하고 치과의사-환자 간 소통을 유연하게 합니다.</div>
					</a>
				</li>
			</ul>
	</div>
</div>

<?php
include_once(G5_THEME_PATH.'/tail.php');
?>