<?php
function render_pagination($total_items, $items_per_page, $current_page, $base_url, $page_param = 'page') {
    $total_pages = (int) ceil($total_items / $items_per_page);
    if ($total_pages <= 1) return;

    echo '<div class="jobs-pagination-wrapper"><div class="nav-links">';

    // Copy current GET parameters, excluding "page"
    $query_params = $_GET;
    unset($query_params[$page_param]);

    // Prev button
    if ($current_page > 1) {
        $query_params[$page_param] = $current_page - 1;
        $prev_url = htmlspecialchars($base_url . '?' . http_build_query($query_params));
        echo '<a class="page-numbers prev" href="' . $prev_url . '">«</a>';
    }

    $range = 1;
    $dot_left = false;
    $dot_right = false;

    for ($i = 1; $i <= $total_pages; $i++) {
        if (
            $i == 1 || $i == $total_pages ||
            ($i >= $current_page - $range && $i <= $current_page + $range)
        ) {
            $is_current = ($i == $current_page);
            $query_params[$page_param] = $i;
            $url = htmlspecialchars($base_url . '?' . http_build_query($query_params));
            echo '<a class="page-numbers' . ($is_current ? ' current' : '') . '" href="' . $url . '">' . $i . '</a> ';
        } elseif ($i < $current_page && !$dot_left && $i > 1) {
            echo '<span class="page-dots">...</span> ';
            $dot_left = true;
        } elseif ($i > $current_page && !$dot_right && $i < $total_pages) {
            echo '<span class="page-dots">...</span> ';
            $dot_right = true;
        }
    }

    // Next button
    if ($current_page < $total_pages) {
        $query_params[$page_param] = $current_page + 1;
        $next_url = htmlspecialchars($base_url . '?' . http_build_query($query_params));
        echo '<a class="page-numbers next" href="' . $next_url . '">»</a>';
    }

    echo '</div></div>';
}
?>