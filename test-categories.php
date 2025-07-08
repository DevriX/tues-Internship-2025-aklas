<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Category Test</title>
  <style>
    .tags-list {
      list-style: none;
      padding: 0;
      display: flex;
      flex-wrap: wrap;
    }

    .list-item {
      margin: 0 5px 5px 0;
    }

    .list-item-link {
      padding: 5px 10px;
      background-color: #eee;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
    }

    .hidden-category {
      display: none;
    }

    #category-tags-list.expanded .hidden-category {
      display: inline-block;
    }

    .show-more-li {
      display: none;
    }

    #category-tags-list.expanded .show-more-li button {
      content: '-';
    }
  </style>
</head>
<body>

<ul class="tags-list" id="category-tags-list">
  <?php
  for ($i = 1; $i <= 25; $i++) {
    $hidden_class = $i > 10 ? ' hidden-category' : '';
    echo '<li class="list-item' . $hidden_class . '"><a href="#" class="list-item-link">Category ' . $i . '</a></li>';
  }
  ?>
  <li class="list-item show-more-li"><button id="show-more-categories" class="list-item-link">+</button></li>
</ul>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const categoryList = document.getElementById('category-tags-list');
  const showMoreBtn = document.getElementById('show-more-categories');
  const showMoreLi = categoryList.querySelector('.show-more-li');

  if (!categoryList || !showMoreBtn || !showMoreLi) return;

  const hiddenItems = categoryList.querySelectorAll('.hidden-category');

  if (hiddenItems.length > 0) {
    showMoreLi.style.display = 'inline-block';

    showMoreBtn.addEventListener('click', function () {
      categoryList.classList.toggle('expanded');
      showMoreBtn.textContent = categoryList.classList.contains('expanded') ? '-' : '+';
    });
  } else {
    showMoreLi.style.display = 'none';
  }
});
</script>

</body>
</html>
