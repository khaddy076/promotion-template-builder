<?php 
if (!@$id) {
 show_404();exit;
}
include "template/header.php";
include "template/sidebar.php";
?>

<div class="main-panel">
    <div class="content-wrapper">
      <div>
        <div class="col-12">
          <section class="content">
      <!-- Main row -->
            <h4>System Modules</h4>

            <div class="alert alert-info"><b>NOTE:</b> Write permission means the user can both read and write to make changes on the page. It is therefore the highest permission a user can have on a page. <br>
            <b>NOTE:</b> Also that your update only take effect when your changes is saved by clicking the save button below.
            </div>
            <div style="border-bottom: solid 2px #ccc;margin-bottom: 15px;"></div>
            <div class="content-area">
              <div>
                <div class="row">
                <?php 
                  $param = array(
                    array('id'=>'r','value'=>'Read'),
                     array('id'=>'w','value'=>'Write')
                  );
                 ?>
                  <?php foreach ($allPages as $module => $pages): ?>
                  <?php 
                      $state='';
                      if ($permitPages[$module]['state']===1) {
                        $state='box-default box-solid';
                      }
                       if ($permitPages[$module]['state']===2) {
                        $state='box-success box-solid';
                      }
                       if ($permitPages[$module]['state']===0) {
                        $state='box-default';
                      }
                   ?>
                  <div class="col-md-4">
                    <div class="box-green box <?php echo $state; ?> collapsed-box" style="height: auto;">
                      <div class="box-header with-border">
                        <h3 class="box-title"><?php echo $module; ?> </h3>
                        <div class="box-tools pull-right">
                          <button type="button" style="margin-top:1px;" class="btn btn-box-tool" data-widget="collapse"><i class="mdi mdi-plus" style="color:#606C84;font-size:18px;"></i>
                          </button>
                        </div>
                        <!-- /.box-tools -->
                      </div>
                      <!-- /.box-header -->
                      <div class="box-body">
                        <ul style="list-style: none;">
                        <?php foreach ($pages['children'] as $key => $page): ?>
                          <li>
                            <div class="permission-content form-group row">
                              <input type="hidden" value="<?php echo $page; ?>" class="pageLink">
                              <lable class="col-sm-5 col-form-label"><?php echo $key; ?></lable>
                              <div class="col-sm-5">
                                <select class="form-control">
                                  <option value="">..deny..</option>
                                  <?php echo buildOption($param,isset($allStates[$page])?$allStates[$page]:''); ?>
                                </select>
                              </div>
                            </div>
                          </li>
                        <?php endforeach; ?>
                        </ul>
                      </div>
                      <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                  </div>
                  <?php endforeach; ?>

                    <div class="clearfix"></div>
                </div>
              <?php if (true): ?>
                <form id="perm_form" action="<?php echo base_url('ajaxData/savePermission'); ?>" method="post">
                  <input type="hidden" value="<?php echo $id; ?>" name="role">
                  <input type="hidden" name="remove" id="perm_remove">
                  <input type="hidden" name="update" id="perm_update">
                  <input type='submit' value='Save' name='sub' class="btn btn-primary save float-right">
                </form>
              <?php endif; ?>
              </div>
          </section>
        </div>
      </div>
    </div>
  <!-- content-wrapper ends -->
  <!-- partial:partials/_footer.html -->
    <?php include_once 'template/sub_foot.php'; ?>
  <!-- partial -->
</div>
<!-- main-panel ends -->

<!-- note this must not be remove so as to balance the div element -->
</div>
<!-- page-body-wrapper ends -->
<?php include "template/footer.php"; ?>
<script>
    $(document).ready(function($){
      $('#perm_form').submit(function(event) {
        event.preventDefault();
        var update=[];
        var remove=[];
        $('.permission-content').each(function(index, el) {
          var link =$(this).children('.pageLink').val();
          var perm = $(this).find('select').val();
          if (!perm) {
            remove.push(link);
          }
          else{
            update.push({path:link,permission:perm});
          }
        });
        //stringify the content and send it to the server straight
        var removeString = JSON.stringify(remove);
        var updateString = JSON.stringify(update);
        $('#perm_remove').val(removeString);
        $('#perm_update').val(updateString);
        submitAjaxForm($(this));
      });
    });

    function ajaxFormSuccess(target,data) {
      if (data.status) {
        reportAndRefresh(target,data);
        return;
      }
      showNotification(data.status,data.message);
        
    }
  </script>
