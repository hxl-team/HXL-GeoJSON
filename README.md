# HXL GeoJSON API

A PHP script to export all refugee numbers at different levels of our administrative unit hierarchy for a given emergency. The script will return point data (centroids in the case where the original geometry of a feature is complex), so that the data can be easily placed on top of a base map.

# Quickstart

Call the script with three arguments:

* **emergency** – URI of the emergency (required). The script returns all population counts related to the given emergency. If you don't know the URI for your emergency, please consult [this list of emergencies](http://sparql.carsten.io/?query=prefix%20hxl%3A%20%3Chttp%3A//hxl.humanitarianresponse.info/ns/%23%3E%0A%0ASELECT%20*%20WHERE%20%7B%0A%20%20%3Femergency%20a%20hxl%3AEmergency%20%3B%0A%20%20%20%20%20%20%20%20%20%20%20%20%20hxl%3AcommonTitle%20%3Ftitle%20.%0A%7D&endpoint=http%3A//hxl.humanitarianresponse.info/sparql) we currently have in HXL. 

* **level** – the admin level (optional). The refugee numbers can be accumulated across the administrative hierarchy for each country. E.g. if you ask for level=0 (i.e., country level), the script will return the total number of all refugees for each country related to that emergency, including all numbers for each of the admin units in the country (recursively, down to the level of [APLs](http://hxl.humanitarianresponse.info/ns/#APL) and [populated places](http://hxl.humanitarianresponse.info/ns/#PopulatedPlace)). If the parameter is omitted, the script will return the lowest-level refugee numbers (at APL and populated place level).

* **callback** - a callback function (optional). If a value for the callback parameter is passed to the script, it will wrap the GeoJSON encoding in a callback function to support [JSONP](http://en.wikipedia.org/wiki/JSONP) requests.

# Sample Queries

We have an instance of this script running at `http://hxl.humanitarianresponse.info/api/refugees.php`:
* : This query will return the refugee counts for the Mali crisis (based on our test data) at APL and populated place level.

* : This query will return the refugee counts for the Mali crisis (based on our test data) accumulated at admin level 1, wrapped in a callback function called `parseJSON`.