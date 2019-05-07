<?php
session_start();
// Initialisation of passwords for the database
include('pdo.inc.php');

// Read the credentials if given as POST parameters
$user = '';
$pwd = '';
$message = '';

$logged= false;
if(isset($_SESSION['user'])){
  $logged = true;
  header("Location: listPatients.php");
  exit();
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
    if($user !=''){
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
	header("Location: listPatients.php");
	exit();
      }
      else{ // if login failed
	$message= "Login not possible";
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
  <h1>Medizininformatik Data Base: Login page</h1>

    <form method='POST'>
<pre>
    Username: <input type="text" name="user">
    Password: <input type="password" name="pwd">
   <input type="submit" value="Login">
</pre>
   </form>

   <?php
    echo "<b>$message</b>";
    }
  ?>