The next step is how do we want to implement the configuration options. Should we use a button / switch that
triggers the fonfiguration function when it is set; or should we simply call the configuration function if 
the device is not connected to the desired network. 

Lest's analyze the no-switch option. We would in this case check if the device is connected to "MIT GUEST" and
if it is not, we would run the configuration function. Then after the connection is successful, we will save the 
new network to the flash memory. The next time we run the device, we will have the function check if the device is
connected to the saved network. If it is not, then the function will execute. But suppose the device is connected
to a particular network and we want to have it connect to a different network. Then how do we do that? Should we 
simply have the device always accessible from outside to be reprogrammed? Perhaps we should because we may 
choose to mount the device at some location and then not have to worry about being in direct contact with it
ever again. 

There is one big potential problem with the whole approach of configuring the device over WIFI. We want the device
to remember its configuration state after it has been powered off. Not only during power off events, but also
when resuming from deepSleep. We want to be able to flash the variable values into read-only memory. 

One solution to this problem is storing the data to EEPROM and then retrieving it from there at boot time.

switch that lasts for a 5 minutes when pressed.