<?php

use yii\widgets\LinkPager;

$script = <<< JS
    $('.form-control').on('change', function() {
      console.log(this.value);
      $.ajax({
      type: "GET",
	  url: '?col='+this.value,
	  success: function(data) {
          $('#content').html(data);
	  }
	});
    })
JS;
$this->registerJs($script, yii\web\View::POS_READY);
?>
<?php
foreach ($model as $item){ ?>
        <div class="well">
            <h3><?=$item->title?></h3>
            <p>
                <?=$item->description?>
            </p>
            <a class="btn btn-primary" href="<?=\yii\helpers\Url::to(['/post/view', 'id' => $item->id])?>">Read more</a>
        </div>
<?php    } ?>
<?=LinkPager::widget(['pagination' => $pages]); ?>

<form>
    <label>
        <select class="form-control">
            <option value="" selected disabled>Select one</option>
            <option class="item" value="3">3 Pages</option>
            <option class="item" value="5">5 Pages</option>
            <option class="item" value="8">8 Pages</option>
            <option class="item" value="10">10 Pages</option>
        </select>
    </label>
</form>
<!---<script>
    let select = document.querySelector('.form-control');

    select.onchange = function () {
        window.location.replace(`/post/index?col=${this.value}`);
    };

</script>--!>
