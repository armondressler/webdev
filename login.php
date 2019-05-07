<?php
session_start();
// Initialisation of passwords for the database
include('pdo.inc.php');

// Read the credentials if given as POST parameters
$user = '';
$pwd = '';


$logged= false;
if(isset($_SESSION['user'])){
  $logged = true;
}

if(!$logged){
  if(isset($_POST['user'])){
    $user = ($_POST['user']);
  }
  if(isset($_POST['pwd'])){
    $pwd = ($_POST['pwd']);
  }


  try {
    // Connect to the database
    $dbh = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    // if the username is set, test if combination "username/password" is valid
    if($username !=''){
      // Initialise SQL query with place holders (:username and :password)
      $sql0 = "SELECT staff.staffID, staff.username, first_name, hashed_password
  FROM staff,credential
  WHERE staff.staffID = credential.staffID AND staff.username=:username AND hashed_password=sha(:password)";
      // parse the query and set the parameters for place holders.
      $statement0 = $dbh->prepare($sql0);
      $statement0->bindParam(':username', $user, PDO::PARAM_STR);
      $statement0->bindParam(':password', $pwd, PDO::PARAM_STR);
      // execute the query
      $result0 = $statement0->execute();
      // case if login was a success
      if($line = $statement0->fetch()){
	echo "<h1> staff : ".$line['staffID']."  ".$line['username']." ".$line['hashed_password']."</h1>\n";
	$logged=true;
	$_SESSION['user']= $line['username'];
      }
      else{ // if login failed
	echo "<h1>Login not possible</h1>";
      }

      $dbh = null;
    }
  }
  catch(PDOException $e)
    {

      /*** echo the sql statement and error message ***/
      echo $e->getMessage();
    }
}

// the form is only displayed if the person is not logged.
if(!$logged){
?>
<form method='POST'>
   <input type="text" name="user">
   <input type="password" name="pwd">
   <input type="submit" value="Login">
   </form>

   <?php
    }
else{

  echo "You are loged in:".$_SESSION['user'];
  echo "<a href='logout.php'>Logout</a>";
  }

  ?>