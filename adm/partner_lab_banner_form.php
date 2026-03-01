<?php
$sub_menu = "300830";
require_once './_common.php';

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$w = $id ? 'u' : '';

if ($w == 'u') {
    $sql = "SELECT * FROM {$g5['partner_lab_banner_table']} WHERE id = '$id'";
    $banner = sql_fetch($sql);
    if (!$banner) {
        alert('존재하지 않는 배너입니다.');
    }
} else {
    $banner = array(
        'title' => '',
        'image_path' => '',
        'link_url' => '',
        'alt_text' => '',
        'is_new_window' => 0,
        'sort_order' => 0,
        'is_active' => 1
    );
}

$g5['title'] = '파트너랩 배너 ' . ($w == 'u' ? '수정' : '등록');
require_once './admin.head.php';
?>

<form name="fbanner" method="post" action="./partner_lab_banner_update.php" enctype="multipart/form-data" onsubmit="return fbanner_submit(this);">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="id" value="<?php echo $id; ?>">

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?></caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
            <tr>
                <th scope="row"><label for="title">배너 제목<strong class="sound_only">필수</strong></label></th>
                <td>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($banner['title']); ?>" 
                           id="title" required class="required frm_input" size="50" maxlength="255">
                </td>
            </tr>
            <tr>
                <th scope="row">배너 이미지</th>
                <td>
                    <?php if ($w == 'u' && $banner['image_path']) { ?>
                        <div class="current_image">
                            <p>현재 이미지:</p>
                            <img src="<?php echo $banner['image_path']; ?>" alt="현재 배너" style="max-width: 300px; max-height: 200px; border: 1px solid #ddd;">
                            <p><input type="checkbox" name="del_image" value="1" id="del_image"> <label for="del_image">이미지 삭제</label></p>
                        </div>
                    <?php } ?>
                    <input type="file" name="banner_image" id="banner_image" accept="image/*" class="frm_input">
                    <p class="frm_info">권장 크기: 1200x400px, 최대 파일 크기: 5MB</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="alt_text">이미지 설명(ALT)</label></th>
                <td>
                    <input type="text" name="alt_text" value="<?php echo htmlspecialchars($banner['alt_text']); ?>" 
                           id="alt_text" class="frm_input" size="50" maxlength="255">
                    <p class="frm_info">이미지에 대한 설명을 입력하세요. (SEO 및 접근성 향상)</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="link_url">링크 URL</label></th>
                <td>
                    <input type="url" name="link_url" value="<?php echo htmlspecialchars($banner['link_url']); ?>" 
                           id="link_url" class="frm_input" size="70" maxlength="500">
                    <p class="frm_info">배너 클릭 시 이동할 URL을 입력하세요. (http:// 또는 https:// 포함)</p>
                </td>
            </tr>
            <tr>
                <th scope="row">새창 열기</th>
                <td>
                    <input type="radio" name="is_new_window" value="1" id="new_window_yes" <?php echo $banner['is_new_window'] ? 'checked' : ''; ?>>
                    <label for="new_window_yes">예</label>
                    
                    <input type="radio" name="is_new_window" value="0" id="new_window_no" <?php echo !$banner['is_new_window'] ? 'checked' : ''; ?>>
                    <label for="new_window_no">아니오</label>
                    
                    <p class="frm_info">링크 클릭 시 새창에서 열지 여부를 선택하세요.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="sort_order">정렬 순서</label></th>
                <td>
                    <input type="number" name="sort_order" value="<?php echo $banner['sort_order']; ?>" 
                           id="sort_order" class="frm_input" min="0" max="999">
                    <p class="frm_info">숫자가 작을수록 먼저 표시됩니다.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">사용 여부</th>
                <td>
                    <input type="radio" name="is_active" value="1" id="active_yes" <?php echo $banner['is_active'] ? 'checked' : ''; ?>>
                    <label for="active_yes">사용</label>
                    
                    <input type="radio" name="is_active" value="0" id="active_no" <?php echo !$banner['is_active'] ? 'checked' : ''; ?>>
                    <label for="active_no">미사용</label>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <input type="submit" value="확인" class="btn_submit btn" accesskey="s">
    <a href="./partner_lab_banner.php" class="btn btn_02">목록</a>
</div>

</form>

<script>
function fbanner_submit(f) {
    if (!f.title.value.trim()) {
        alert('배너 제목을 입력해주세요.');
        f.title.focus();
        return false;
    }
    
    <?php if ($w != 'u') { ?>
    if (!f.banner_image.value) {
        alert('배너 이미지를 선택해주세요.');
        f.banner_image.focus();
        return false;
    }
    <?php } ?>
    
    if (f.banner_image.value) {
        var file = f.banner_image.files[0];
        if (file) {
            // 파일 크기 체크 (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('이미지 파일 크기는 5MB 이하여야 합니다.');
                return false;
            }
            
            // 파일 형식 체크
            var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (allowedTypes.indexOf(file.type) === -1) {
                alert('JPG, PNG, GIF 형식의 이미지만 업로드 가능합니다.');
                return false;
            }
        }
    }
    
    if (f.link_url.value && !isValidUrl(f.link_url.value)) {
        alert('올바른 URL 형식을 입력해주세요. (http:// 또는 https:// 포함)');
        f.link_url.focus();
        return false;
    }
    
    return true;
}

function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

// 이미지 미리보기
document.getElementById('banner_image').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('image_preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.id = 'image_preview';
                preview.innerHTML = '<p>미리보기:</p><img id="preview_img" style="max-width: 300px; max-height: 200px; border: 1px solid #ddd;">';
                e.target.parentNode.appendChild(preview);
            }
            document.getElementById('preview_img').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<style>
.current_image {
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.current_image img {
    display: block;
    margin: 10px 0;
}

.frm_info {
    color: #666;
    font-size: 12px;
    margin-top: 5px;
}

#image_preview {
    margin-top: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

input[type="radio"] {
    margin-right: 5px;
}

label {
    margin-right: 15px;
}
</style>

<?php
require_once './admin.tail.php';
?>