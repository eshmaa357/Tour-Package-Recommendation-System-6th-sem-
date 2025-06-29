<?php
  function get_recommendation() {
    include('includes/config.php');
    // 1. Initialize a priority queue, weightage  
    $pq = new SplPriorityQueue();
    $weight = 0;
    $pid=intval($_GET['pkgid']);

    // 2. Retrieve current object and store it in $curResult
    $sql = "SELECT * FROM tbltourpackages WHERE PackageId='$pid'";
    $sth = $dbh->prepare($sql);
    $sth->execute();
    $curResult = $sth->fetch(PDO::FETCH_OBJ);
    
    // 3. Retrieve all objects except the current object
    $sql = "SELECT * FROM tbltourpackages WHERE NOT packageID='$pid'";
    $sth = $dbh->prepare($sql);
    $sth->execute();
    $results=$sth->fetchAll(PDO::FETCH_OBJ);
    


    /* 4.
      * Compare the current object with all the other
      * objects in the database for similarity,
      * Increase the weightage according to similar types.
        Pacakge Type, Package Location, PackagePrice, 
        Package Features are taken into consideration.
      * You can also take other properties for recommendation.
        More the number of properties compared, better the 
        recommendation.
      * We can assign different weights to different properties.
      * In this case, I have used 1 for consistency.
      * You can assign different weights to different properties,
        according to how you see fit. *
    */
    // print_r(count($results));
    foreach($results as $res) {
      if ($res->PackageType == $curResult->PackageType) {
        $weight += 1;
      }
      else if ($res->PackageLocation == $curResult->PackageLocation) {
        $weight += 1;
      }
      else if ($res->PackagePrice == $curResult->PackagePrice) {
        $weight += 1;
      }
      else if ($res->PackageFetures == $curResult->PackageFetures) {
        $weight += 1;
      }

      /* 5.
        * Insert the object into the priority queue
          we initialized. And reset the weight value to zero.
        * Since it's a priority queue, its ordering will be 
          according to the weight it carries.
        * Higher the weight, Higher the similarity  
      */
      $pq->insert($res, $weight);
      $weight = 0;   
    }


    // 6. Loop throught the queue and display the results
    // Most similar results will be displayed first since it's 
    // a priority queue.
    $count = 0;
    while($pq->valid()){
      ?>
        <div class="rom-btm">
          <div class="col-md-3 room-left wow fadeInLeft animated" data-wow-delay=".3s">
            <img src="admin/pacakgeimages/<?php echo htmlentities($pq->current()->PackageImage);?>" class="img-responsive" alt="">
          </div>
          <div class="col-md-6 room-midle wow fadeInUp animated" data-wow-delay=".3s">
            <h4>Package Name: <?php echo htmlentities($pq->current()->PackageName);?></h4>
            <h6>Package Type : <?php echo htmlentities($pq->current()->PackageType);?></h6>
            <p><b>Package Location :</b> <?php echo htmlentities($pq->current()->PackageLocation);?></p>
            <p><b>Features</b> <?php echo htmlentities($pq->current()->PackageFetures);?></p>
          </div>
          <div class="col-md-3 room-right wow fadeInRight animated" data-wow-delay=".3s">
            <h5>Rs <?php echo htmlentities($pq->current()->PackagePrice);?></h5>
            <a href="package-details.php?pkgid=<?php echo htmlentities($pq->current()->PackageId);?>" class="view">Details</a>
          </div>
          <div class="clearfix"></div>
        </div>
      
      <?php 
          // 7. Move to the next object
          $pq->next(); 
          $count += 1;

          // 8. Limit the number of pacakges displayed to 3
          // You can change it.
          if ($count == 5) {
            return;
          }

        }
      }
?>