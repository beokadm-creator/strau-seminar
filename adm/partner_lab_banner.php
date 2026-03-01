<?php
$sub_menu = "300830";
require_once './_common.php';

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$g5['title'] = '파트너랩 배너 관리';
require_once './admin.head.php';

// 배너 목록 조회
$sql = "SELECT * FROM {$g5['partner_lab_banner_table']} ORDER BY sort_order ASC, created_at DESC";
$result = sql_query($sql);
?>

<div class="local_desc01 local_desc">
    <p>파트너랩 메인 페이지에 표시될 배너를 관리합니다.</p>
</div>

<div class="btn_fixed_top">
    <a href="./partner_lab_banner_form.php" class="btn btn_01">배너 추가</a>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">배너 이미지</th>
                <th scope="col">제목</th>
                <th scope="col">링크 URL</th>
                <th scope="col">새창</th>
                <th scope="col">순서</th>
                <th scope="col">사용</th>
                <th scope="col">등록일</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 0;
            while ($row = sql_fetch_array($result)) {
                $bg = 'bg' . ($i % 2);
                $i++;
            ?>
            <tr class="<?php echo $bg; ?>">
                <td class="td_num"><?php echo $row['id']; ?></td>
                <td class="td_img">
                    <?php if ($row['image_path']) { ?>
                        <img src="<?php echo $row['image_path']; ?>" alt="배너 이미지" style="max-width: 100px; max-height: 60px;">
                    <?php } else { ?>
                        <span class="no-image">이미지 없음</span>
                    <?php } ?>
                </td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td>
                    <?php if ($row['link_url']) { ?>
                        <a href="<?php echo $row['link_url']; ?>" target="_blank" class="link-preview">
                            <?php echo htmlspecialchars($row['link_url']); ?>
                        </a>
                    <?php } else { ?>
                        <span class="no-link">링크 없음</span>
                    <?php } ?>
                </td>
                <td class="td_mng">
                    <?php echo $row['is_new_window'] ? '예' : '아니오'; ?>
                </td>
                <td class="td_num"><?php echo $row['sort_order']; ?></td>
                <td class="td_mng">
                    <span class="<?php echo $row['is_active'] ? 'txt_true' : 'txt_false'; ?>">
                        <?php echo $row['is_active'] ? '사용' : '미사용'; ?>
                    </span>
                </td>
                <td class="td_datetime"><?php echo substr($row['created_at'], 0, 10); ?></td>
                <td class="td_mng">
                    <a href="./partner_lab_banner_form.php?id=<?php echo $row['id']; ?>" class="btn btn_03">수정</a>
                    <a href="./partner_lab_banner_delete.php?id=<?php echo $row['id']; ?>" 
                       onclick="return confirm('정말 삭제하시겠습니까?');" class="btn btn_02">삭제</a>
                </td>
            </tr>
            <?php
            }
            
            if ($i == 0) {
                echo '<tr><td colspan="9" class="empty_table">등록된 배너가 없습니다.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<style>
.td_img {
    text-align: center;
    padding: 10px;
}

.td_img img {
    border: 1px solid #ddd;
    border-radius: 4px;
}

.no-image, .no-link {
    color: #999;
    font-style: italic;
}

.link-preview {
    color: #0066cc;
    text-decoration: none;
    max-width: 200px;
    display: inline-block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.link-preview:hover {
    text-decoration: underline;
}

.txt_true {
    color: #28a745;
    font-weight: bold;
}

.txt_false {
    color: #dc3545;
    font-weight: bold;
}
</style>

<?php
require_once './admin.tail.php';
?>