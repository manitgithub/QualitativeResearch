<?php $this->layout = 'noTheme';?>
<div style="padding: 10px 10px 10px 10px">
                <div class="box-header with-border">
                  <h3 class="box-title">เพิ่มคำถาม</h3>
                  <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                  </div><!-- /.box-tools -->
                </div><!-- /.box-header -->
                <div class="box-body">
<?php echo $this->renderPartial('_form', array('model'=>$model)); ?></div> </div> </div>