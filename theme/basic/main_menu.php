<?php
	$depth1_list = array(); 
	$depth2_list = array(); 
	$depth3_list = array(); 
	$depth4_list = array(); 

	$sql = "SELECT * FROM category WHERE length(cateno) = '3' ORDER BY cate_exposure ASC ";
	$result = sql_query($sql);
	for ($i=0; $row=sql_fetch_array($result); $i++) {
		$depth1_list[] = $row;
	}
	
	$sql = "SELECT * FROM category WHERE length(cateno) = '6' ORDER BY cate_exposure ASC ";
	$result = sql_query($sql);
	for ($i=0; $row=sql_fetch_array($result); $i++) {
		$depth2_list[] = $row;
	}
	
	$sql = "SELECT * FROM category WHERE length(cateno) = '9' ORDER BY cate_exposure ASC ";
	$result = sql_query($sql);
	for ($i=0; $row=sql_fetch_array($result); $i++) {
		$depth3_list[] = $row;
	}
	
	$sql = "SELECT * FROM category WHERE length(cateno) = '12' ORDER BY cate_exposure ASC ";
	$result = sql_query($sql);
	for ($i=0; $row=sql_fetch_array($result); $i++) {
		$depth4_list[] = $row;
	}

	// $sql = " SELECT * FROM category WHERE cateno = '{$cateno}' ";
	// $cate_bg = sql_fetch($sql);
	// if($row['cate_img']){
	// 	$ctbg = " style=\"background:url(".G5_DATA_URL."/category/".$cate_bg['cate_img'].") no-repeat 50%; background-color:".$row['cate_bg']."\" ";
	// }
?>

<ul class="gnb_dep1">
	<li class="menu-item-has-children <?php if ($depth1 == 6) echo "on"; ?>"><a href="<?=G5_URL ?>/launchingShow">런칭쇼</a></li>
  	<li class="menu-item-has-children <?php if ($depth1 == 1) echo "on"; ?>"><a href="<?=G5_URL ?>/campus">캠퍼스</a></li>
  	<!-- <li class="menu-item-has-children sub <?php if ($depth1 == 2) echo "on"; ?>"><a href="javascript:void(0);">제품정보</a>
		<ul class="sub_menu">
	      <li <?php if ($depth1 == 2 && $depth2 == 1) echo "class='on'"; ?>><a href="<?=G5_URL ?>/implant?sca=Straumann">임플란트</a>
				<ul class="sub_depth3_menu">
					<li <?php if ($depth1 == 2 && $depth2 == 1 && $depth3 == 1) echo "class='on'"; ?>><a href="<?=G5_URL ?>/implant?sca=Straumann">Straumann</a></li>
					<li <?php if ($depth1 == 2 && $depth2 == 1 && $depth3 == 2) echo "class='on'"; ?>><a href="<?=G5_URL ?>/digital_solution?sca=Anthogyr">Anthogyr</a></li>
				</ul>
			</li>
	      <li <?php if ($depth1 == 2 && $depth2 == 2) echo "class='on'"; ?>><a href="<?=G5_URL ?>/digital_solution?sca=Scanning">디지털 솔루션</a>
				<ul class="sub_depth3_menu">
					<li <?php if ($depth1 == 2 && $depth2 == 2 && $depth3 == 1) echo "class='on'"; ?>><a href="<?=G5_URL ?>/implant?sca=Scanning">Scanning</a></li>
					<li <?php if ($depth1 == 2 && $depth2 == 2 && $depth3 == 2) echo "class='on'"; ?>><a href="<?=G5_URL ?>/digital_solution?sca=planning＆design">planning＆design</a></li>
					<li <?php if ($depth1 == 2 && $depth2 == 2 && $depth3 == 1) echo "class='on'"; ?>><a href="<?=G5_URL ?>/implant?sca=Manufacturing">Manufacturing</a></li>
					<li <?php if ($depth1 == 2 && $depth2 == 2 && $depth3 == 2) echo "class='on'"; ?>><a href="<?=G5_URL ?>/digital_solution?sca=Prosthetic">Prosthetic</a></li>
				</ul>
			</li>
			<li <?php if ($depth1 == 2 && $depth2 == 3) echo "class='on'"; ?>><a href="<?=G5_URL ?>/biomaterial?sca=Emdogain">바이오머테리얼</a>
				<ul class="sub_depth3_menu">
					<li <?php if ($depth1 == 2 && $depth2 == 3 && $depth3 == 1) echo "class='on'"; ?>><a href="<?=G5_URL ?>/implant?sca=Emdogain">Emdogain</a></li>
					<li <?php if ($depth1 == 2 && $depth2 == 3 && $depth3 == 2) echo "class='on'"; ?>><a href="<?=G5_URL ?>/digital_solution?sca=Bone Grafts">Bone Grafts</a></li>
					<li <?php if ($depth1 == 2 && $depth2 == 3 && $depth3 == 3) echo "class='on'"; ?>><a href="<?=G5_URL ?>/digital_solution?sca=Anthogyr">Memberanes</a></li>
				</ul>
			</li>
	    </ul>
	</li> -->
	<li class="menu-item-has-children sub more <?php if ($depth1 == 2) echo "on"; ?>"><a href="<?=G5_URL ?>/sub/product_list">제품정보</a>
		<ul class="sub_menu">
		<?php 
		// [1차 카테고리]
		for ($i=0; $i < count($depth1_list); $i++) { 
			?>
			<li class="<?=$cateno == $depth1_list[$i]['cateno'] ? "on":""?>">
				<a href="<?=G5_URL."/sub/list.php?cateno={$depth1_list[$i]['cateno']}&cate1={$depth1_list[$i]['cateno']}"?>" ><?=$depth1_list[$i]['catenm']?></a>
				<ul class="sub_depth2_menu">
					<?
					// [2차 카테고리]
					for ($j=0; $j < count($depth2_list); $j++) { 
						if(!($depth1_list[$i]['cateno'] == substr($depth2_list[$j]['cateno'], 0 ,3))) continue;
						$depth2 = $depth2_list[$j];
						$depth2['url'] = G5_URL."/sub/list.php?cateno={$depth2_list[$j]['cateno']}&cate1={$depth1_list[$i]['cateno']}&cate2={$depth2_list[$j]['cateno']}";
					?>
					<li class="<?=$cateno == $depth2_list[$j]['cateno'] ? "on":""?>">
						<a href="<?=$depth2['url']?>" ><?=$depth2['catenm']?></a>
						<?php if(!($depth2['catenm'] == 'Emdogain')) { ?>
						<ul class="sub_depth3_menu">
							<?
							// [3차 카테고리]
							for ($k=0; $k < count($depth3_list); $k++) { 
								if(!($depth2_list[$j]['cateno'] == substr($depth3_list[$k]['cateno'], 0 ,6))) continue;
								$depth3 = $depth3_list[$k];
								$depth3['url'] = G5_URL."/sub/list.php?cateno={$depth3_list[$k]['cateno']}&cate1={$depth1_list[$i]['cateno']}&cate2={$depth2_list[$j]['cateno']}&cate3={$depth3_list[$k]['cateno']}";
							?>
							<li class="<?=$cateno == $depth3_list[$k]['cateno'] ? "on":""?>">
								<a href="<?=$depth3['url']?>" ><?=$depth3['catenm']?></a>
								<ul class="sub_depth4_menu">
									<?
									// [4차 카테고리]
									for ($l=0; $l < count($depth4_list); $l++) { 
										if(!($depth3_list[$k]['cateno'] == substr($depth4_list[$l]['cateno'], 0 ,9))) continue;
										$depth4 = $depth4_list[$l];
										$depth4['url'] = G5_URL."/sub/list.php?cateno={$depth4_list[$l]['cateno']}&cate1={$depth1_list[$i]['cateno']}&cate2={$depth2_list[$j]['cateno']}&cate3={$depth3_list[$k]['cateno']}&cate4={$depth4_list[$l]['cateno']}";
									?>
									<li class="<?=$cateno == $depth4_list[$l]['cateno'] ? "on":""?>">
										<a href="<?=$depth4['url']?>" ><?=$depth4['catenm']?></a>
									</li>
									<? } ?>
								</ul>
							</li>
							<? } ?>
						</ul>
						<? } ?>
					</li>
					<? } ?>
				</ul>
			</li>
		<? } ?>
		
		</ul>
	</li>
  	<li class="menu-item-has-children <?php if ($depth1 == 3) echo "on"; ?>"><a href="<?=G5_URL ?>/video">영상자료</a></li>
  	<li class="menu-item-has-children <?php if ($depth1 == 4) echo "on"; ?>"><a href="<?=G5_URL ?>/science">사이언스</a></li>
	<li class="menu-item-has-children <?php if ($depth1 == 5) echo "on"; ?>"><a href="<?php echo G5_BBS_URL ?>/qawrite">문의</a></li>
    <li class="menu-item-has-children"><a href="<?=G5_URL ?>/partner_lab/order/index.php">파트너랩 주문</a></li>
</ul>	

