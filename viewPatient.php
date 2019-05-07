<?php
session_start();

// First, we test if user is logged. If not, goto main.php (login page).
if(!isset($_SESSION['user'])){
  header("Location: main.php");
  //echo "problem with user";
  exit();
}


include('pdo.inc.php');

try {
    $dbh = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    $patientID=0;
    if(isset($_GET['id'])){
      $patientID = (int)($_GET['id']);
    }
    if($patientID >0){

      $sql0 = "SELECT name, first_name
  FROM patient
  WHERE patient.patientID = :patientID";

    $statement0 = $dbh->prepare($sql0);
    $statement0->bindParam(':patientID', $patientID, PDO::PARAM_INT);
    $result0 = $statement0->execute();

    while($line = $statement0->fetch()){
      echo "<h1> Patient : ".$line['first_name']."  ".$line['name']."</h1>";

      echo "<br>\n";
    }


      /*** echo a message saying we have connected ***/
      $sql = "SELECT name, first_name, value, time, sign_name
  FROM patient, vital_sign, sign
  WHERE patient.patientID = vital_sign.patientID
    AND vital_sign.signID = sign.signID 
    AND patient.patientID = :patientID";

    $statement = $dbh->prepare($sql);
    $statement->bindParam(':patientID', $patientID, PDO::PARAM_INT);
    $result = $statement->execute();

    while($line = $statement->fetch()){
      echo $line['sign_name']." = ".$line['value']. " at ".$line['time'];

      echo "<br>\n";
    }

    }
    else{
      echo "<h1>The patient does not exist</h1>";
    }

    //$dbh = null;
}
catch(PDOException $e)
{

    /*** echo the sql statement and error message ***/
    echo $e->getMessage();
}


?>
<h2> Exercise 1</h2>
Buttons for displaying just an alert.<br>
<button onclick="alert('Temperature');">Temperature</button>
  <button onclick="alert('Pulse');">Pulse</button><button onclick="alert('Activity');">Activity</button>

<br>
<script>
  function writeMessage(msg){
    document.getElementById("sign").textContent=msg;
  }
  </script>
  <h3 id="sign">Test </h3>
  Buttons for displaying in the previous H3 placeholder.<br>
<button onclick="writeMessage('Temperature');">Temperature</button>
  <button onclick="writeMessage('Pulse');">Pulse</button><button onclick="writeMessage('Activity');">Activity</button>

  
<h2> Exercise 2</h2>
<script>
  function displayVitalSigns(sign){
    var list = document.getElementsByClassName("signs");
    for(var i in list){
      if(list[i].style !== undefined){
	list[i].style.display="none";
      }
    }
    var list2 = document.getElementsByClassName(sign);
    if(list2){
      for(var i2 in list2){
	if(list2[i2].style !== undefined){
	  list2[i2].style.display="block";
	}
      }
    }
    else{
      alert('no list');
    }
  }
  </script>
<style>
.Temperature {
    display: none;
    }
.Pulse {
    display: none;
 }
  .Activity {
        display: none;

       }

  </style>
  
Buttons for displaying just an alert.<br>
<button onclick="displayVitalSigns('Temperature');">Temperature</button>
  <button onclick="displayVitalSigns('Pulse');">Pulse</button><button onclick="displayVitalSigns('Activity');">Activity</button>



  <?php
  try {

      if($patientID >0){
      $sql = "SELECT name, first_name, value, time, sign_name
  FROM patient, vital_sign, sign
  WHERE patient.patientID = vital_sign.patientID
    AND vital_sign.signID = sign.signID 
    AND patient.patientID = :patientID";

    $statement = $dbh->prepare($sql);
    $statement->bindParam(':patientID', $patientID, PDO::PARAM_INT);
    $result = $statement->execute();

    while($line = $statement->fetch()){
      echo "<div class='signs ".$line['sign_name']."'>".$line['value']. " at ".$line['time']."</div>\n";

    }

    }
    else{
      echo "<h1>The patient does not exist</h1>";
    }

    $dbh = null;
}
catch(PDOException $e)
{

    /*** echo the sql statement and error message ***/
    echo $e->getMessage();
}

  ?>
  
<br />
<i><a href="logout.php">Logout</a></i>
