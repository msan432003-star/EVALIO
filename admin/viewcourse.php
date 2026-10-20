<?php
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();
?>
<?php include('header.php'); ?>
<style>
    .panel-heading{
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: rgba(106, 0, 245, 0.87);
    border: rgba(106, 0, 245, 0.87);
    font-size: 35px;
   
}
</style>
<div class="panel-heading">My Exams Overview</div>

    <div class="container_gray_bg" id="home_feat_1">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <table  border="1" class="table" style="margin-top:100px;">
                    <tr>
                        <th>Sr No</th>
                        <th>Course Name</th>
                        <th>EDIT/DELETE</th>
                    </tr>
<?php
    $actions=array(
        'edit'=>array('label'=>'Edit','link'=>'editcourse.php','params'=>array('id'=>'id'),'attributes'=>array('class'=>'btn btn-success')),
        // confirm prompt added so an accidental click doesn't delete a course
        'delete'=>array('label'=>'Delete','link'=>'deletecourse.php','params'=>array('id'=>'id'),'attributes'=>array('class'=>'btn btn-success','onclick'=>"return confirm('Are you sure you want to delete this course?');"))
    );

    $config=array(
        'srno'=>true,
        'hiddenfields'=>array('id')
    );

    $join=array(
    );

    $fields=array('id','course_name');

    $users=$dao->selectAsTable($fields,'courses',1,$join,$actions,$config);

    echo $users;
?>
                </table>
            </div>
        </div><!-- End row -->
    </div><!-- End container -->
    </div><!-- End container_gray_bg -->

<?php include('footer.php'); ?>