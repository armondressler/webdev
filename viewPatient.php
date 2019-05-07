<?php
session_start();

// First, we test if user is logged. If not, goto main.php (login page).
if(!isset($_SESSION['user'])){
  header("Location: main.php");
  //echo "problem with user";
  exit();
}

// set the html head
?>
<html>
  <head>
    <title>Patient's vital signs</title>
    <link href="style.css" type="text/css" rel="stylesheet">
  </head>
  <body>

<?php
  include('pdo.inc.php');

  try {
      $dbh = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
      $patientID=0;
      if(isset($_GET['id'])){
        $patientID = (int)($_GET['id']);
      }
/*** if the selected patientID does not exist, state that  ***/
      if($patientID <=0){
        echo "<h1>The patient does not exist</h1>";
      }else{

/*** echo a message saying we have connected: Display the Patient Name in the Header ***/
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
     
// set the buttons to choose one of the vital signs available
        echo "<br/>";
        $sql = "select sign_name from sign";
        $result = $dbh->query($sql);
        while($vs = $result->fetch()){
          echo "<input type=\"button\" ".
          "value=\"".$vs['sign_name']." \" ".
          "name=\"btn".$vs['sign_name']." \" ".
          "onClick=\"changeH3('".$vs['sign_name']."')\"/>".
          "<br/>";

        }

?>
    <script type="text/javascript">
      function changeH3($vitalSignsToShow) {

//put all vital signs into an array, and set their display property to 'none' (i.e., hide them)
        var arrDiv = Array.prototype.slice.call(document.getElementsByTagName("div"));
        for(var i in arrDiv){
          arrDiv[i].style.display = 'none';
        };

  //display the vital sign type (according to what button was pressed) as a header
        document.getElementById("sign").textContent = $vitalSignsToShow;

  //put all vital signs of the chosen type into an array, and set their display property to 'block' (i.e., unhide them)
        var arrShow = Array.prototype.slice.call(document.getElementsByClassName($vitalSignsToShow));
        for(var i in arrShow){
          arrShow[i].style.display = 'block';
        };
      }
    </script>
    <br />
      <?php

  //Insert a <h3> placeholder for displaying the vital sign type chosen according to what button was pressed
        echo "<h3 id=\"sign\"></h3>";


  
        $sql = "SELECT name, first_name, value, time, sign_name
        FROM patient, vital_sign, sign
        WHERE patient.patientID = vital_sign.patientID
          AND vital_sign.signID = sign.signID 
          AND patient.patientID = :patientID";

        $statement = $dbh->prepare($sql);
        $statement->bindParam(':patientID', $patientID, PDO::PARAM_INT);
        $result = $statement->execute();

/*** display the vital signs ***/
        while($line = $statement->fetch()){
          echo "<div class = \"".$line['sign_name']."\">\n".
            "\t".$line['value']. " at ".$line['time']."\n".
            "</div>\n";
        }


// set the controls to add a vital sign
  //set a dropdown list for available vital signs
        echo "<br/><br/><br/><h3 id=\"addSign\">Add a vital sign measurement</h3>";
?>
    <form method="GET" action="viewPatient.php">
        <select name = "dropdown_vs">
<?php
        $sql = "select sign_name from sign";
        $result = $dbh->query($sql);
        while($vs = $result->fetch()){
          echo "<option value = \"".$vs['sign_name']."\"> ".$vs['sign_name']."</option>";
        }
?>       
        </select>
        <input type="hidden" name="id" value="<?php echo $_GET['id']?>"/>
        <input type="text" name="newMeasurement" placeholder="value" value="" size="9" />
        <input type="text" name="note" placeholder="note about value" value="" size="18" />
        <input type="submit" name="AddValue" value="add and display"/>
    </form>    
<?php
if(isset($_GET['newMeasurement'])){
  $sql= "select signID from sign where sign_name='".$_GET['dropdown_vs']."'";
  $result = $dbh->query($sql);
    
  $vsID=$result->fetch();
        
  $sql = "INSERT
    INTO vital_sign
   VALUES (
      '',
      '".$_GET['id']."',
      '".$vsID[0]."',
      '".$_GET['newMeasurement']."',
      CURRENT_TIMESTAMP,
      '".$_GET['note']."'
    )";
  $dbh->exec($sql);
  changeH3('$_GET[\'dropdown_vs\']');
}


?>
    <br />
<?php     





      }
    $dbh = null;
}
catch(PDOException $e)
{

    /*** echo the sql statement and error message ***/
    echo $e->getMessage();
}


?>



</br></br></br>
<i><a href="logout.php">Logout</a></i> 

</body>
</html>

