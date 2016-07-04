//TO DO ON 6/24/2016:
//    If the server settings did not change then don't run the configuration commands such as transmission power and ssid+password setting.
//    To implement this, you must compare here the latest values for the settings with the previusly stored values and then execute the commands only if 
//    there was a change. You should store the values obtained from the server in a String ARRAY, and then you can cast the power value string to an integer.
//    That way, you can just compare the prevArray[] with currentArray[], rather than each value separately.
//TO DO LATER:
//    Have a setting on in the JSON array + Here for changing the update frequency.
//    Have a setting on the website + here for changing the SSID.
//    Implement sleep mode when off is selected. 
//    To reduce power consumption even more, you can change the mode to AP when not reading data from server.
//    
//UPDATE on 6/24/2016:
//    1.Simpler method for removing the HTTP header. Use the fact that the json is the last item in the server response. Store only the last line read.
//    TODO.Parse the json in a separate function, rather than in the main loop directly.
//    TODO.Read the SSID from the .json file on the web and set it to that value.
//UPDATE on 6/29/2016:
//    Added controllable deepsleep instruction when in OFF state. The amount of time for deep sleep is specified from the web interface.
//    change d1 to state1 and so on for the other variables too.
//    TODO: Create an activity diagram for this code.
//UPDATE on 6/30/2016:
//    Changed the baud rate to 74880 bits/sec. In order to see the automatically generated boot messages, we must use this baud rate.
#include <ESP8266httpUpdate.h>
#include <ESP8266WiFi.h>
#include <ArduinoJson.h>
extern "C" {
  #include "user_interface.h";
  //Espressif's APIs can be found from: https://espressif.com/en/support/download/documents?keys=&field_type_tid%5B%5D=14
  }

//##################--CONTROL-PARAMETERS--#################
//these must be char pointers because the functions where they are used expect variables of this type.
const char *ssid      = "MIT GUEST";
const char *password  = ""; 
//const char *ssid      = "rays";
//const char *password  = "100MemorialDrive";        
const char *website   = "myesp8266.netau.net"; 
const char *path      = "/light.json";
const char *espPSWD   = "myPassW00D";
//------------------------------------------------------------------------------------
boolean state1 = true;
int     delay1 = 5000; //time in ms between successive requests
uint8_t power1 = 40;   //initial power level
char  ssid1[]  = "ESP_SSID_NULL";
//#########################################################
//========================================================= 
void setup() {  
  Serial.begin(74880); //Use exactly this boad rate, because the device automatically generates boot messages readable only at this baud rate.
  Serial.println();
  Serial.print("Connecting to ");
  Serial.println(ssid);
  WiFi.mode(WIFI_AP_STA);   //set mode to be both access point & station
  WiFi.begin(ssid, password); //connect to the specified network 
  Serial.println("Connected; IP address: " + WiFi.localIP());
  WiFi.softAP("ESP_Null", "MyNetworkPassword");
  system_phy_set_max_tpw(power1);
}

void loop() {  
  Serial.print("Connecting to myesp8266.netau.net... ");
  WiFiClient client;
  if (client.connect(website, 80)) {    //connect to the host server && test if connection succeeded.
    Serial.println("<-Connected->");
    //-------------BEGIN: HTTP GET Request-----------
    client.println("GET "+String(path)+" HTTP/1.1");
    client.println("Host: " + String(website)); //must be cast to String type,
    client.println("Connection: close");        //where as above it must be char.
    client.println();
    //-------------END: HTTP GET Request-------------    
  }
  else{ //if the connection is successful, then send an HTTP request
    Serial.println("<-Connection Failed->");
    return;
  } 
  //--------------BEGIN: Waiting for HTTP Response ---------
  Serial.print("Waiting for HTTP response ");
  while(!client.available()){ //while there are no bytest available to be read:
    Serial.print(".");
    delay(100);
  }
  Serial.println("Response received: \n");
  //-------------END: Waiting for HTTP Response-------------

  //The server has now returned a response which is stored in a buffer in our 'client' variable. We must now read that response.
  //================================================================================================
  //-------------BEGIN: Read the HTTP Response & set parameters based on JSON data------------------

  /*
   *The server response contains an HTTP header + blank line + json array. Since we know that the json is the last line, we can simply read  
   *each line into the same variables and keep only the last line. We read one line at a time then check 
   *if there is more content to read. If there is, then we read the next line and overwrite the variable, and so on. 
   */
  String line;
  while(client.available()){
    line = client.readStringUntil('\n'); //each line ends with "\r\n" except the last line, which ends with '}'. But '}' is OK, beccause if the
    Serial.println(line);                  //terminating character is not found, this just keeps reading until it reaches the end of the data.
  }
  char json[line.length()+1];
  line.toCharArray(json, line.length()+1);
  
  StaticJsonBuffer<300> myJsonBuffer; //byte size must be greater than or equal to the size of the json array (size=chars+spaces+\n+\r)
  JsonObject& myJsonObj = myJsonBuffer.parseObject(json);
  if (!myJsonObj.success()){
    Serial.println("parseObject() failed");
    return;
  }

  //Check if a variables has changed on the server since last check, and if so only then apply that change to the ISP's settings.
  state1=myJsonObj["state1"];
  delay1 = myJsonObj["delay1"]; //this value is in SECONDS.
  if(state1 == false){    //if OFF is chosen, then we put system to sleep without executing any of the remaining code. When it wakes up, it starts from top of the code.
    Serial.print("Entering deep sleep for: (seconds) ");
    Serial.println(delay1);
    ESP.deepSleep(delay1*1000*1000, WAKE_RFCAL);
  }
  if(strcmp(ssid1,myJsonObj["ssid1"]) != 0){ //If the ssid1 variable has changed:
    strcpy(ssid1,myJsonObj["ssid1"]);  //Save the new value into the String variable ssid1.
    WiFi.softAP(ssid1, espPSWD);       //Set the SSID of the board to the new value. Only pointer allowed for arguments.
    Serial.print("\nSSID changed to: ");
    Serial.println(ssid1);
  }else{
    Serial.println("\nSSID stayed the same: ");
    Serial.println(ssid1);
  }
  if(power1 != myJsonObj["power1"]){ //If the power1 variable has changed:
    power1 = myJsonObj["power1"];    //Save the new value into the uint8_t variable power1.
    system_phy_set_max_tpw(myJsonObj["power1"]); //Set the transmission power to the new value.
    Serial.print("Tx power changed to: ");
    Serial.println(power1);
  } 
  client.stop(); //disconnect from the server

  //if we have come here, then deep sleep has not been invoked. Here we tell the system to wait for the specified time until it checks the server again.
  //we can save a little extra power perhaps by settig the mode to transmit only rather than Tx&Rx, but it may not lead to much if any power savings. 
  Serial.print("Delay time until next refresh: (seconds) ");
  Serial.println(delay1);
  Serial.println("Staying Awake until then.");
  delay(delay1*1000); //Wait until the next request from server.

  //-------------END: Read the HTTP Response & set parameters based on JSON data--------------
  //============================================================================================
  
}
