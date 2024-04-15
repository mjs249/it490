<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content=""width=device-width,
	initial-scale1.0">
	<title>Explore Restaurants</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>

<ul class="nav nav-pills">
  <li class="nav-item">
    <a class="nav-link active" aria-current="page" href="welcome.php">Home</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="user.Profile.php">Profile</a>
  </li>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Search</a>
    <ul class="dropdown-menu">
      <li><a class="dropdown-item" href="#">Search by Location</a></li>
      <li><a class="dropdown-item" href="#">Search with Dietary Filters</a></li>
    </ul>
  </li>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Review</a>
    <ul class="dropdown-menu">
      <li><a class="dropdown-item" href="review.php">Leave a Review</a></li>
      <li><a class="dropdown-item" href="displayReviews.php">Show Reviews</a></li>
    </ul>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="booking.php">Reservations</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="showRecs.php">Recommendations</a>
  </li>
</ul>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html> 
