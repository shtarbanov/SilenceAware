<!--
RECALL: 
  When the "Submit" button is pressed, the 'name':'value' pairs from an HTML <form> are stored in a "$_POST" array.
  The '$_POST' array is sent to that page which is specified in the "action" field of the HTML <form>. The browser is
  also redirected to that page. If we set the form's action as blank (action=""), then the "$_POST" array is sent to 
  the current page. Moreover, the last item in the "$_POST" array will be the key:value pair of 'submit':'submit'. 
  Thus, we can chechk whether a form was submitted using the syntax "if(isset($_POST['submit'])){....}"

APPROACH DESCRIPTION: 
  Set the HTML form's action as blank, so that when the form is submitted, the "$_POST" array will 
  be sent to the same page; browser too will be redirected to the same page (will refresh bascially):
        <form action="" method="POST">
  We would then convert the contents of the "$_POST" array into a JSON stirng & write that sting to a .json file:
        file_put_contents("light.json", json_encode($_POST));
  But since the last item in the "$_POST" array is 'submit':'submit', and since we don't want that item to be in our .json
  file, we should first remove that key:value pair from the "$_POST" array before converting it into a JSON string:
        unset($_POST['submit']);
  We also want the radio buttons to reflect the current contents of the .json file so that it would be possible to change the
  state only selected devices while effectively maintaining the state of the other devices unchanged. I say effectively because
  when you hit submit everything in the .json file is overwrritten, but if a 'yes' is overwritten with 'yes' then there is no
  change efecctively. We read the contents of the .json file and set our variables as follows:
        $myJSONstring=file_get_contents("light.json");
        $myArray=json_decode($myJSONstring, true); 
        $state_d1=$myArray['state1'];
        $state_d2=$myArray['state2'];
        $state_d3=$myArray['state3'];
        $state_d4=$myArray['state4'];
    ->Then in our form we embed a PHP shorthand-if-statement that echoses either 'checked' or '' depending on whether
      each of these 4 PHP variables is ON or OF. Thus for the case of the "$state_d1" variable we would write into the form:
        ?php  echo ($state_d1=="1")?  "checked" : ""; ?
        ?php  echo ($state_d1=="0")? "checked" : ""; ?
  Since the form is sent to the same page, we have an extra problem to worry about. When the page is loaded BY THE USER, it
  contains an empty "$_POST" array, and we don't want to write the contents of the empty array to the .json file because that
  would destroy the current data. To check whether the page was loaded by the user or whether it was loaded automatically after
  the submit button was pressed, we look at whether the 'submit':'submit' key-value pair is present in the "$_POST" array. It it
  is not present, then we display a message "the form is not yet submitted". If it is present, then we display a 
  message "the form was submitted successfuly". 

  UPDATE on 6/20/2016:
  We are now adding a field to the form where you can select the transmission power level form a drop-down list for each device. We are using
  a PHP loop that automatically generates the values from 0 to 80; otherwise we would have to type them all manually if we do it in HTML.

  UPDATE on 6/21/2016:
  Check whether the .json file is actually present. Make the PHP code more efficient.

  UPDATE on 6/24/2016:
  Add a form field for selecting the SSID from a drop-down menu. (optionally add a custom item in the dropdown list for manually specifying it).
  Add a form field for selecting the update interval from a drop-down menu.
  
  UPDATE on 6/26/2016:
  In the $_POST array & thus also the JSON array, by default all keys & values are of string type and surrounded by double quotes. 
  If we keep it that way, then we would have to read the value associated with each key as a String in the arduino code and only then convert 
  the string to the appropriate type within the Arduino. This is wasting resources. We would rather do this conversion here where resources 
  are not limited. Thus, for those value in the JSON array that should not be of type string, we want to remove their quotes, then would be able
  to read the numeric values in our arduino code directly as type int*. To achieve this conversion in PHP, we perform the conversion on the 
  approapriate values within the $_POST array by using the function settype(variable, type).
  NOTICE that this actually makes the size of the json array significatly smaller too, because dozens of quote characters are removed. Thus we
    also save resources on our microcontroller also from the fact that we are storing less bytes. And we save tyme as well.
  
  UPDATE on 6/29/2016:
  Change d1 to state1 and so on for all the variables.
  TODO: In the form, change the state values from "1" and "0" to "true" and "false" 
  
  TO UPDATE:
  The SSID and Passwords parameters could be retrieved from a separate webpage with secure login. So basically, the ESP module would have to 
  login to another website in addition to the main one. We would have a flag in the JSON array on the main wesite, and when that flag is set, the
  esp would be asked to establish a connection with a second website WITH login from where it would be able to retrieve the ssid to which it must
  connect, and the corresponding passsword.
    But this approach seems too complicated. A much better approach would be to have the ability to connect to the module directly over wifi, set
  the parameters you want to set and be done with it. Basically, have it operate as a router; that is have it open a configuration page when 
  a user connects to the device. Then in that configuration page, expose all of the settings that can be set. 
-->
<html>  
  <head>      
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>LED for ESP8266</title>

    <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <link href="custom.css" rel="stylesheet">
    <?php
      if(empty($_POST)){
        echo "Form is not yet submitted.";
      } 
      else if (isset($_POST['submit'])){
        echo "Form was submitted successfully with contents: <br>";
        echo print_r($_POST);
        unset($_POST['submit']);

        //By default, the key-value pairs in the POST array are all strings, and therefore all are in quotes. This means that if
        //we keep those defaults, we would have to read the values as strings in the arduino code and then convert the numeric values to int.
        //Since on the ESP8266, we have limited resources and we want to minimize power consumption, it is much better to do the conversion here
        //and have numeric values be as numbers, without quotes around them. And the same applies for foolean values. Hence we make
        //this type conversion in the POST array. Then when we encode that array into json, the non-string values will be without quotes.
        //==========begin type conversion===================
        settype($_POST['state1'], bool);
        settype($_POST['state2'], bool);
        settype($_POST['state3'], bool);
        settype($_POST['state4'], bool);
        settype($_POST['power1'], int);
        settype($_POST['power2'], int);
        settype($_POST['power3'], int);
        settype($_POST['power4'], int);
        settype($_POST['delay1'], int);
        settype($_POST['delay2'], int);
        settype($_POST['delay3'], int);
        settype($_POST['delay4'], int);
        //==========end type conversion =====================

        file_put_contents("light.json", json_encode($_POST));
      }

      //check whether the .json file is present, and if so then read it.
      if(file_exists("light.json")){
        echo "<br><br> The contents of the .json file are: <br>";
        $contents = file_get_contents("light.json"); //read the file
        echo $contents; //then echo the contets of the file to the screen.
        $myArray=json_decode($contents, true); //don't forget to put the second argument or you will get an object rather than assoc array.
        
        //============BEGIN: Read values from the JSON======================
        //We do this only for the sake of populating the values in the HTML form with the previously selected values.
        $state_d1=$myArray['state1']; //d stands for device.
        $state_d2=$myArray['state2'];
        $state_d3=$myArray['state3'];
        $state_d4=$myArray['state4'];
        $power_d1=$myArray['power1'];
        $power_d2=$myArray['power2'];
        $power_d3=$myArray['power3'];
        $power_d4=$myArray['power4'];
        $ssid_d1=$myArray['ssid1'];
        $ssid_d2=$myArray['ssid2'];
        $ssid_d3=$myArray['ssid3'];
        $ssid_d4=$myArray['ssid4'];
        $updateTime_d1=$myArray['delay1'];
        $updateTime_d2=$myArray['delay2'];
        $updateTime_d3=$myArray['delay3'];
        $updateTime_d4=$myArray['delay4'];
        //===============END: Read values from the JOSN=======================
      }
      else{
        echo "<br><br> The .json file does not exist.";
      }
    ?>

  </head>
  <body>
    <div class="container">
      <form action="" method="POST">      <!-- Setting action to "" submits the form the current page -->  
        <div class="row" style="margin-top: 20px;">  
          <div class="col-md-8 col-md-offset-2">
            <h1>ESP8266 Devices</h1>
          </div>
        </div>
        <div class="row" style="margin-top: 20px;">  
          <div class="col-md-8 col-md-offset-2">
            <label class="text-inline" style="margin-right: 10px"><p>Device1:</p></label>
            <label class="radio-inline"><input name="state1" value="1" type="radio" <?php  echo ($state_d1==true)?  "checked>" : ">"; ?>  ON </label>
            <label class="radio-inline"><input name="state1" value="0" type="radio" <?php  echo ($state_d1==false)? "checked>" : ">"; ?>  OFF </label>
            <label style="margin-left: 20px;">P_Tx:</label>
            <select name="power1">
                <?php for($i=0;$i<=82;$i++){
                        echo "<option value=\"".$i."\" ";
                        echo ($i==$power_d1)? "selected=\"selected\"": ""; //Outputs either {{selected="selected"}} OR {{null}}.
                        echo ">".$i."</option>";
                      }
                ?>
            </select>
            <label style="margin-left: 20px;">SSID: </label>
            <select name="ssid1"> 
                <option value="ESP_SSID_A" <?php echo ($ssid_d1=="ESP_SSID_A")? "selected=\"selected\"":""; ?> >ESP_SSID_A</option>             
                <option value="ESP_SSID_B" <?php echo ($ssid_d1=="ESP_SSID_B")? "selected=\"selected\"":""; ?> >ESP_SSID_B</option>
                <option value="ESP_SSID_C" <?php echo ($ssid_d1=="ESP_SSID_C")? "selected=\"selected\"":""; ?> >ESP_SSID_C</option>
                <option value="ESP_SSID_D" <?php echo ($ssid_d1=="ESP_SSID_D")? "selected=\"selected\"":""; ?> >ESP_SSID_D</option>
            </select>
            <label style="margin-left: 20px;">Update Interval: </label>
            <select name="delay1"> 
                <option value="5"   <?php echo ($updateTime_d1=="5")?   "selected=\"selected\"":""; ?> >5 s</option>             
                <option value="10"  <?php echo ($updateTime_d1=="10")?  "selected=\"selected\"":""; ?> >10 s</option>
                <option value="30"  <?php echo ($updateTime_d1=="30")?  "selected=\"selected\"":""; ?> >30 s</option>
                <option value="60"  <?php echo ($updateTime_d1=="60")?  "selected=\"selected\"":""; ?> >1 min</option>
                <option value="120" <?php echo ($updateTime_d1=="120")? "selected=\"selected\"":""; ?> >2 min</option>
                <option value="300" <?php echo ($updateTime_d1=="300")? "selected=\"selected\"":""; ?> >5 min</option>
                <option value="600" <?php echo ($updateTime_d1=="600")? "selected=\"selected\"":""; ?> >10 min</option>                
            </select>

          </div>
        </div>
        <!--EndRow-->
        <div class="row" style="margin-top: 20px;">  
          <div class="col-md-8 col-md-offset-2">
            <label class="text-inline" style="margin-right: 10px"><p>Device2:</p></label>
            <label class="radio-inline"><input name="state2" value="1" type="radio" <?php echo ($state_d2==true)?  "checked>" : ">"; ?> ON </label>
            <label class="radio-inline"><input name="state2" value="0" type="radio" <?php echo ($state_d2==false)? "checked>" : ">"; ?> OFF </label>
            <label style="margin-left: 20px;">P_Tx:</label>
            <select name="power2">
                <?php for($i=0;$i<=82;$i++){
                        echo "<option value=\"".$i."\" ";
                        echo ($i==$power_d2)? "selected=\"selected\"": ""; //Outputs either {{selected="selected"}} OR {{null}}.
                        echo ">".$i."</option>";
                      }
                ?>
            </select>
            <label style="margin-left: 20px;">SSID: </label>
            <select name="ssid2"> 
                <option value="ESP_SSID_A" <?php echo ($ssid_d2=="ESP_SSID_A")? "selected=\"selected\"":""; ?> >ESP_SSID_A</option>             
                <option value="ESP_SSID_B" <?php echo ($ssid_d2=="ESP_SSID_B")? "selected=\"selected\"":""; ?> >ESP_SSID_B</option>
                <option value="ESP_SSID_C" <?php echo ($ssid_d2=="ESP_SSID_C")? "selected=\"selected\"":""; ?> >ESP_SSID_C</option>
                <option value="ESP_SSID_D" <?php echo ($ssid_d2=="ESP_SSID_D")? "selected=\"selected\"":""; ?> >ESP_SSID_D</option>
            </select>
            <label style="margin-left: 20px;">Update Interval: </label>
            <select name="delay2"> 
                <option value="5"   <?php echo ($updateTime_d2=="5")?   "selected=\"selected\"":""; ?> >5 s</option>             
                <option value="10"  <?php echo ($updateTime_d2=="10")?  "selected=\"selected\"":""; ?> >10 s</option>
                <option value="30"  <?php echo ($updateTime_d2=="30")?  "selected=\"selected\"":""; ?> >30 s</option>
                <option value="60"  <?php echo ($updateTime_d2=="60")?  "selected=\"selected\"":""; ?> >1 min</option>
                <option value="120" <?php echo ($updateTime_d2=="120")? "selected=\"selected\"":""; ?> >2 min</option>
                <option value="300" <?php echo ($updateTime_d2=="300")? "selected=\"selected\"":""; ?> >5 min</option>
                <option value="600" <?php echo ($updateTime_d2=="600")? "selected=\"selected\"":""; ?> >10 min</option>                
            </select>
          </div>
        </div>
        <!--EndRow-->
        <div class="row" style="margin-top: 20px;">  
          <div class="col-md-8 col-md-offset-2">
            <label class="text-inline" style="margin-right: 10px"><p>Device3:</p></label>
            <label class="radio-inline"><input name="state3" value="1" type="radio" <?php echo ($state_d3==true)?  "checked>" : ">"; ?> ON </label>
            <label class="radio-inline"><input name="state3" value="0" type="radio" <?php echo ($state_d3==false)? "checked>" : ">"; ?> OFF </label>
            <label style="margin-left: 20px;">P_Tx:</label>
            <select name="power3">
                <?php for($i=0;$i<=82;$i++){
                        echo "<option value=\"".$i."\" ";
                        echo ($i==$power_d3)? "selected=\"selected\"": ""; //Outputs either {{selected="selected"}} OR {{null}}.
                        echo ">".$i."</option>";
                      }
                ?>
            </select>
            <label style="margin-left: 20px;">SSID: </label>
            <select name="ssid3"> 
                <option value="ESP_SSID_A" <?php echo ($ssid_d3=="ESP_SSID_A")? "selected=\"selected\"":""; ?> >ESP_SSID_A</option>             
                <option value="ESP_SSID_B" <?php echo ($ssid_d3=="ESP_SSID_B")? "selected=\"selected\"":""; ?> >ESP_SSID_B</option>
                <option value="ESP_SSID_C" <?php echo ($ssid_d3=="ESP_SSID_C")? "selected=\"selected\"":""; ?> >ESP_SSID_C</option>
                <option value="ESP_SSID_D" <?php echo ($ssid_d3=="ESP_SSID_D")? "selected=\"selected\"":""; ?> >ESP_SSID_D</option>
            </select>
            <label style="margin-left: 20px;">Update Interval: </label>
            <select name="delay3"> 
                <option value="5"   <?php echo ($updateTime_d3=="5")?   "selected=\"selected\"":""; ?> >5 s</option>             
                <option value="10"  <?php echo ($updateTime_d3=="10")?  "selected=\"selected\"":""; ?> >10 s</option>
                <option value="30"  <?php echo ($updateTime_d3=="30")?  "selected=\"selected\"":""; ?> >30 s</option>
                <option value="60"  <?php echo ($updateTime_d3=="60")?  "selected=\"selected\"":""; ?> >1 min</option>
                <option value="120" <?php echo ($updateTime_d3=="120")? "selected=\"selected\"":""; ?> >2 min</option>
                <option value="300" <?php echo ($updateTime_d3=="300")? "selected=\"selected\"":""; ?> >5 min</option>
                <option value="600" <?php echo ($updateTime_d3=="600")? "selected=\"selected\"":""; ?> >10 min</option>                
            </select>
          </div>
        </div>
        <!--EndRow-->
        <div class="row" style="margin-top: 20px;">  
          <div class="col-md-8 col-md-offset-2">
            <label class="text-inline" style="margin-right: 10px;"><p>Device4:</p></label>
            <label class="radio-inline"><input name="state4" value="1" type="radio" <?php echo ($state_d4==true)?  "checked>" : ">"; ?> ON </label>
            <label class="radio-inline"><input name="state4" value="0" type="radio" <?php echo ($state_d4==false)? "checked>" : ">"; ?> OFF </label>
            <label style="margin-left: 20px;">P_Tx:</label>
            <select name="power4">
                <?php for($i=0;$i<=82;$i++){
                        echo "<option value=\"".$i."\" ";
                        echo ($i==$power_d4)? "selected=\"selected\"": ""; //Outputs either {{selected="selected"}} OR {{null}}.
                        echo ">".$i."</option>";
                      }
                ?>
            </select>
            <label style="margin-left: 20px;">SSID: </label>
            <select name="ssid4"> 
                <option value="ESP_SSID_A" <?php echo ($ssid_d4=="ESP_SSID_A")? "selected=\"selected\"":""; ?> >ESP_SSID_A</option>             
                <option value="ESP_SSID_B" <?php echo ($ssid_d4=="ESP_SSID_B")? "selected=\"selected\"":""; ?> >ESP_SSID_B</option>
                <option value="ESP_SSID_C" <?php echo ($ssid_d4=="ESP_SSID_C")? "selected=\"selected\"":""; ?> >ESP_SSID_C</option>
                <option value="ESP_SSID_D" <?php echo ($ssid_d4=="ESP_SSID_D")? "selected=\"selected\"":""; ?> >ESP_SSID_D</option>
            </select>
            <label style="margin-left: 20px;">Update Interval: </label>
            <select name="delay4"> 
                <option value="5"   <?php echo ($updateTime_d4=="5")?   "selected=\"selected\"":""; ?> >5 s</option>             
                <option value="10"  <?php echo ($updateTime_d4=="10")?  "selected=\"selected\"":""; ?> >10 s</option>
                <option value="30"  <?php echo ($updateTime_d4=="30")?  "selected=\"selected\"":""; ?> >30 s</option>
                <option value="60"  <?php echo ($updateTime_d4=="60")?  "selected=\"selected\"":""; ?> >1 min</option>
                <option value="120" <?php echo ($updateTime_d4=="120")? "selected=\"selected\"":""; ?> >2 min</option>
                <option value="300" <?php echo ($updateTime_d4=="300")? "selected=\"selected\"":""; ?> >5 min</option>
                <option value="600" <?php echo ($updateTime_d4=="600")? "selected=\"selected\"":""; ?> >10 min</option>                
            </select>
          </div>
        </div>
        <!--EndRow-->
        <div class="row" style="margin-top: 20px;">  
          <div class="col-md-8 col-md-offset-2">
            <input type="submit" name="submit" value="submit">
          </div>
        </div>
        <!--EndRow-->
      </form>
    </div>
    <!--End container-->

   
  </body>
</html>  
