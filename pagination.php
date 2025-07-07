<?php
function render_pagination($total_items, $items_per_page, $current_page, $base_url, $page_param = 'page') {
    $total_pages = (int) ceil($total_items / $items_per_page);
    if ($total_pages <= 1) return;
    echo '<div class="jobs-pagination-wrapper"><div class="nav-links">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $is_current = ($i == $current_page);
        $url = htmlspecialchars($base_url . (strpos($base_url, '?') !== false ? '&' : '?') . $page_param . '=' . $i);
        echo '<a class="page-numbers' . ($is_current ? ' current' : '') . '" href="' . $url . '">' . $i . '</a> ';
    }
    echo '</div></div>';
}
?>
