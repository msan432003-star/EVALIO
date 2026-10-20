
<?php 
  require('../config/autoload.php');
include("header.php");
?>

<div class="row">
  <!-- Total Applicants -->
  <div class="col-md-3 stretch-card grid-margin">
    <div class="card bg-primary text-white card-img-holder">
      <div class="card-body">
        <!-- <img src="images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image"/> -->
        <h4 class="font-weight-normal mb-3">Total Applicants <i class="ti-user ml-2"></i></h4>
        <?php
         $dao = new DataAccess();
          $app = "SELECT COUNT(*) AS total FROM application";
          $result = $dao->query($app);
          echo "<h2 class='mb-5'>" . $result[0]['total'] . "</h2>";
        ?>
        <h6 class="card-text">Across all recruitments</h6>
      </div>
    </div>
  </div>

  <!-- Applications Pending -->
  <div class="col-md-3 stretch-card grid-margin">
    <div class="card bg-warning text-white card-img-holder">
      <div class="card-body">
        <!-- <img src="images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image"/> -->
        <h4 class="font-weight-normal mb-3">Pending Applications <i class="ti-timer ml-2"></i></h4>
        <?php
          $pending = "SELECT COUNT(*) AS pending FROM application WHERE status='Pending'";
          $result = $dao->query($pending);
          echo "<h2 class='mb-5'>" . $result[0]['pending'] . "</h2>";
        ?>
        <h6 class="card-text">Awaiting review</h6>
      </div>
    </div>
  </div>

  <!-- Applications Approved -->
  <div class="col-md-3 stretch-card grid-margin">
    <div class="card bg-success text-white card-img-holder">
      <div class="card-body">
        <!-- <img src="images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image"/> -->
        <h4 class="font-weight-normal mb-3">Approved <i class="ti-check-box ml-2"></i></h4>
        <?php
          $approved = "SELECT COUNT(*) AS approved FROM application WHERE status='Approved'";
           $result = $dao->query( $approved);
          echo "<h2 class='mb-5'>" . $result[0]['approved'] . "</h2>";
        ?>
        <h6 class="card-text">Successfully processed</h6>
      </div>
    </div>
  </div>

  <!-- Rejected Applications -->
  <div class="col-md-3 stretch-card grid-margin">
    <div class="card bg-danger text-white card-img-holder">
      <div class="card-body">
        <!-- <img src="images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image"/> -->
        <h4 class="font-weight-normal mb-3">Rejected <i class="ti-close ml-2"></i></h4>
        <?php
          $rejected = "SELECT COUNT(*) AS rejected FROM application WHERE status='Rejected'";
          $result = $dao->query( $rejected);
          echo "<h2 class='mb-5'>" . $result[0]['rejected'] . "</h2>";
        ?>
        <h6 class="card-text">Applications not selected</h6>
      </div>
    </div>
  </div>
</div>
