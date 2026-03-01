<?php add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/sub_common.css">', 0); ?>

<!--sub visual-->
<?php
	$depth1_list = array(); 
	$depth2_list = array(); 
	$depth3_list = array(); 
	$depth4_list = array(); 

	$sql = "SELECT * FROM category WHERE length(cateno) = '3' ORDER BY cate_exposure ASC ";
	$result = sql_query($sql);
	for($i=0; $row=sql_fetch_array($result); $i++) {
		$depth1_list[] = $row;
	}
	
	$sql = "SELECT * FROM category WHERE length(cateno) = '6' ORDER BY cate_exposure ASC ";
	$result = sql_query($sql);
	for($i=0; $row=sql_fetch_array($result); $i++) {
		$depth2_list[] = $row;
	}
	
	$sql = "SELECT * FROM category WHERE length(cateno) = '9' ORDER BY cate_exposure ASC ";
	$result = sql_query($sql);
	for($i=0; $row=sql_fetch_array($result); $i++) {
		$depth3_list[] = $row;
	}
	
	$sql = "SELECT * FROM category WHERE length(cateno) = '12' ORDER BY cate_exposure ASC ";
	$result = sql_query($sql);
	for($i=0; $row=sql_fetch_array($result); $i++) {
		$depth4_list[] = $row;
	}

	// $sql = " SELECT * FROM category WHERE cateno = '{$cateno}' ";
	// $cate_bg = sql_fetch($sql);
	// if($row['cate_img']){
	// 	$ctbg = " style=\"background:url(".G5_DATA_URL."/category/".$cate_bg['cate_img'].") no-repeat 50%; background-color:".$row['cate_bg']."\" ";
	// }
?>
<div class="full_container sub_visual <?php if($g5['title2'] == "privacy"){?>privacy<?}?>" >
	<div class="inner_container">
		<div class="visual_cont inner_visual">
			<h2><? echo $g5['title'];?></h2>
		</div>
	</div>
</div>

<!--sub menu-->
<?if($g5['title'] == "회사소개"){?>
<div class="full_container sub_page_menu <? echo $g5['title2']?>_menu">
	<div class="inner_container">
		<ul class="col3 clear">
			<li><a href="/sub/greeting.php" class="<? if($subtit =="회사소개"){echo "active";} ?>">회사소개</a></li>
			<li><a href="/sub/history.php" class="<? if($subtit =="연혁"){echo "active";} ?>">연혁</a></li>
			<li><a href="/sub/global_network.php" class="<? if($subtit =="GLOBAL NETWORK"){echo "active";} ?>">GLOBAL NETWORK</a></li>
		</ul>
	</div>
	<div class="mobile_sub_menu">
		<select name="mobile_sub_menu" class="mobile_sub_menu_sel" onchange="location = this.value;">
			<option value="/sub/greeting.php" class="mobile_sub_menu_op" <?if($subtit =="회사소개"){?>selected<?}?>>회사소개</option>
			<option value="/sub/history.php" <?if($subtit =="연혁"){?>selected<?}?>>연혁</option>
			<option value="/sub/global_network.php" <?if($subtit =="GLOBAL NETWORK"){?>selected<?}?>>GLOBAL NETWORK</option>
		</select>
	</div>
</div>
<?}?>

<?if($g5['title'] == "인재채용"){?>
<div class="full_container sub_page_menu <? echo $g5['title2']?>_menu">
	<div class="inner_container">
		<ul class="col3 clear">
			<li><a href="#talent_area" class="active">인재상</a></li>
			<li><a href="#system_area">인사제도</a></li>
			<li><a href="#welfare_area">복리후생</a></li>
			<li><a href="#recruit_process">채용절차 및 문의</a></li>
		</ul>
	</div>
	<div class="mobile_sub_menu">
		<select name="mobile_sub_menu" class="mobile_sub_menu_sel" onchange="location = this.value;" id="spec">
			<option value="#talent_area" class="mobile_sub_menu_op" selected>인재상</option>
			<option value="#system_area">인사제도</option>
			<option value="#welfare_area">복리후생</option>
			<option value="#recruit_process">채용절차 및 문의</option>
		</select>
	</div>
</div>
<?}?>

<?if($g5['title'] == "Application"){?>
<div class="full_container sub_page_menu <? echo $g5['title2']?>_menu">
	<div class="inner_container">
		<ul class="col3 clear">
			<li><a href="/sub/app.php" class="<? if($subtit =="Application"){echo "active";} ?>">이차전지</a></li>
			<li><a href="/sub/app2.php" class="<? if($subtit =="Application2"){echo "active";} ?>">자동차</a></li>
			<li><a href="/sub/app3.php" class="<? if($subtit =="Application3"){echo "active";} ?>">Display& 전자부품</a></li>
			<li><a href="/sub/app4.php" class="<? if($subtit =="Application4"){echo "active";} ?>">기타부문</a></li>
			<li><a href="/sub/app5.php" class="<? if($subtit =="Application5"){echo "active";} ?>">E-book</a></li>
			<li><a href="/sub/app6.php" class="<? if($subtit =="Application6"){echo "active";} ?>">용접기술연구소</a></li>
		</ul>
	</div>
	<div class="mobile_sub_menu">
		<select name="mobile_sub_menu" class="mobile_sub_menu_sel" onchange="location = this.value;">
			<option value="/sub/app.php" class="mobile_sub_menu_op" <?if($subtit =="Application"){?>selected<?}?>>이차전지</option>
			<option value="/sub/app2.php" <?if($subtit =="Application2"){?>selected<?}?>>자동차</option>
			<option value="/sub/app3.php" <?if($subtit =="Application3"){?>selected<?}?>>Display& 전자부품</option>
			<option value="/sub/app4.php" <?if($subtit =="Application4"){?>selected<?}?>>기타부문</option>
			<option value="/sub/app5.php" <?if($subtit =="Application5"){?>selected<?}?>>E-book</option>
			<option value="/sub/app6.php" <?if($subtit =="Application6"){?>selected<?}?>>용접기술연구소</option>
		</select>
	</div>
</div>
<?}?>

<?if($g5['title'] == "고객센터"){?>
<div class="full_container sub_page_menu <? echo $g5['title2']?>_menu">
	<div class="inner_container">
		<ul class="col3 clear">
			<li><a href="/bbs/board.php?bo_table=notice" class="<? if($subtit =="공지사항"){echo "active";} ?>">공지사항</a></li>
			<li><a href="/sub/contact.php" class="<? if($subtit =="CONTACT US"){echo "active";} ?>">CONTACT US</a></li>
			<li><a href="/sub/location.php" class="<? if($subtit =="오시는길"){echo "active";} ?>">오시는길</a></li>
		</ul>
	</div>
	<div class="mobile_sub_menu">
		<select name="mobile_sub_menu" class="mobile_sub_menu_sel" onchange="location = this.value;">
			<option value="/bbs/board.php?bo_table=notice" class="mobile_sub_menu_op" <?if($subtit =="공지사항"){?>selected<?}?>>공지사항</option>
			<option value="/sub/contact.php" <?if($subtit =="CONTACT US"){?>selected<?}?>>CONTACT US</option>
			<option value="/sub/location.php" <?if($subtit =="오시는길"){?>selected<?}?>>오시는길</option>
		</select>
	</div>
</div>
<?}?>

<?if($g5['title'] == "제품소개"){?>
<div>상단 메뉴 샘플</div>
<div class="header_inner">
	<div class="menu">
		<ul class="gnb_dep1">
			<li class="menu-item-has-children sub <?php if ($depth1 == 2) echo "on"; ?>"><a href="javascript:void(0);">제품정보</a>
				<ul class="sub_menu">
				<?php 
				// [1차 카테고리]
				for ($i=0; $i < count($depth1_list); $i++) { 
					?>
					<li class="<?=$cateno == $depth1_list[$i]['cateno'] ? "on":""?>">
						<a href="<?=G5_URL."/sub/list.php?cateno={$depth1_list[$i]['cateno']}&cate1={$depth1_list[$i]['cateno']}"?>" >[1차] <?=$depth1_list[$i]['catenm']?></a>
						<ul class="sub_depth2_menu">
							<?
							// [2차 카테고리]
							for ($j=0; $j < count($depth2_list); $j++) { 
								if(!($depth1_list[$i]['cateno'] == substr($depth2_list[$j]['cateno'], 0 ,3))) continue;
								$depth2 = $depth2_list[$j];
								$depth2['url'] = G5_URL."/sub/list.php?cateno={$depth2_list[$j]['cateno']}&cate1={$depth1_list[$i]['cateno']}&cate2={$depth2_list[$j]['cateno']}";
							?>
							<li class="<?=$cateno == $depth2_list[$j]['cateno'] ? "on":""?>">
								<a href="<?=$depth2['url']?>" >[2차] <?=$depth2['catenm']?></a>
								<ul class="sub_depth3_menu">
									<?
									// [3차 카테고리]
									for ($k=0; $k < count($depth3_list); $k++) { 
										if(!($depth2_list[$j]['cateno'] == substr($depth3_list[$k]['cateno'], 0 ,6))) continue;
										$depth3 = $depth3_list[$k];
										$depth3['url'] = G5_URL."/sub/list.php?cateno={$depth3_list[$k]['cateno']}&cate1={$depth1_list[$i]['cateno']}&cate2={$depth2_list[$j]['cateno']}&cate3={$depth3_list[$k]['cateno']}";
									?>
									<li class="<?=$cateno == $depth3_list[$k]['cateno'] ? "on":""?>">
										<a href="<?=$depth3['url']?>" >[3차] <?=$depth3['catenm']?></a>
										<ul class="sub_depth4_menu">
											<?
											// [4차 카테고리]
											for ($l=0; $l < count($depth4_list); $l++) { 
												if(!($depth3_list[$k]['cateno'] == substr($depth4_list[$l]['cateno'], 0 ,9))) continue;
												$depth4 = $depth4_list[$l];
												$depth4['url'] = G5_URL."/sub/list.php?cateno={$depth4_list[$l]['cateno']}&cate1={$depth1_list[$i]['cateno']}&cate2={$depth2_list[$j]['cateno']}&cate3={$depth3_list[$k]['cateno']}&cate4={$depth4_list[$l]['cateno']}";
											?>
											<li class="<?=$cateno == $depth4_list[$l]['cateno'] ? "on":""?>">
												<a href="<?=$depth4['url']?>" >[4차] <?=$depth4['catenm']?></a>
											</li>
											<? } ?>
										</ul>
									</li>
									<? } ?>
								</ul>
							</li>
							<? } ?>
						</ul>
					</li>
				<? } ?>
				
				</ul>
			</li>
		</ul>

	</div>
</div>

<div class="full_container sub_page_menu <? echo $g5['title2']?>_menu">
	<div class="inner_container">
		<ul class="col3 clear">
			
		</ul>
	</div>
	<div class="mobile_sub_menu">
		<select name="mobile_sub_menu" class="mobile_sub_menu_sel" onchange="location = this.value;">
			<?php 
				$sql = "SELECT * FROM category WHERE length(cateno) = '3' ORDER BY cate_exposure ASC ";
				$res = sql_query($sql);
				while($row = sql_fetch_array($res)){
					$act = $cateno == $row['cateno'] ? "selected":"";
					?>
					<option value="/sub/list.php?cateno=<?=$row['cateno']?>" class="mobile_sub_menu_op" >1차 카테고리 : <?=$row['catenm']?></option>
					<?
				}
			?>
		</select>
	</div>
</div>
<?}?>	

<!-- //sub menu -->

<script>
	$(function(){
		/* 서브메뉴 크기 지정하기 */
		var x = $("ul.col3 li").length;
		var wid = (100/x);
		$("ul.col3 li").css({"width":wid+"%"});
	});
</script>