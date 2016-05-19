<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
  <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
  <title>Google Maps</title>
  <style type="text/css">
  
  </style>
  <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyBrB6-FAlTNmIEwQkgrwGsHu4jeoHFWCuo&"></script>
  <script type='text/javascript' src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
  <script type='text/javascript' src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script> 
  <script type="text/javascript">
  //<![CDATA[
  
    var map;
    var markers = [];
    var infoWindow;
    var locationSelect;
    
    var directionsDisplay;
    var directionsService;
    
    var autocomplete;
    
    function load() {
      map = new google.maps.Map(document.getElementById("map"), {
        center: new google.maps.LatLng(40, -100),
        zoom: 4,
        mapTypeId: 'roadmap',
        mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}
      });
      
      autocomplete = new google.maps.places.Autocomplete(document.getElementById('addressInput'));
      // getLocation();
      
      map.set('styles', [
      {
        featureType: 'road',
        elementType: 'geometry',
        stylers: [
          { color: '#F5811E' },
          { weight: 0.5 }
        ]
      }, {
        featureType: 'road',
        elementType: 'labels',
        stylers: [
          { saturation: 0 },
          { invert_lightness: true }
        ]
      }, {
        featureType: 'landscape',
        elementType: 'geometry',
        stylers: [
          { hue: '#23B8D9' },
          { gamma: 0.5 },
          { saturation: 60 },
          { lightness: 50 }
        ]
      }
    ]);

      
    infoWindow = new google.maps.InfoWindow();

    locationSelect = document.getElementById("locationSelect");
    locationSelect.onchange = function() {
      var markerNum = locationSelect.options[locationSelect.selectedIndex].value;
      if (markerNum != "none"){
        // console.log(markers[markerNum]);
        google.maps.event.trigger(markers[markerNum], 'click');
      }
    };
      
    directionsDisplay = new google.maps.DirectionsRenderer;
    directionsService = new google.maps.DirectionsService;
    directionsDisplay.setMap(map);
    directionsDisplay.setPanel(document.getElementById('directions'));
    
  }

   function searchLocations() {
     var address = document.getElementById("addressInput").value;
     var geocoder = new google.maps.Geocoder();
     geocoder.geocode({address: address}, function(results, status) {
       if (status == google.maps.GeocoderStatus.OK) {
        searchLocationsNear(results[0].geometry.location);
       } else {
         alert(address + ' not found');
       }
     });
   }

   function clearLocations() {
     infoWindow.close();
     for (var i = 0; i < markers.length; i++) {
       markers[i].setMap(null);
     }
     markers.length = 0;

     locationSelect.innerHTML = "";
     var option = document.createElement("option");
     option.value = "none";
     option.innerHTML = "See all results:";
     locationSelect.appendChild(option);
   }

   function searchLocationsNear(center) {
     clearLocations();
     
     var limit = document.getElementById('limitSelect').value;
     var radius = document.getElementById('radiusSelect').value;
     var searchUrl = 'phpsqlsearch_genxml.php?lat=' + center.lat() + '&lng=' + center.lng() + '&radius=' + radius + '&limit=' + limit;
     downloadUrl(searchUrl, function(data) {
       var xml = parseXml(data);
       var markerNodes = xml.documentElement.getElementsByTagName("marker");
       var bounds = new google.maps.LatLngBounds();
       for (var i = 0; i < markerNodes.length; i++) {
         var name = markerNodes[i].getAttribute("name");
         var address = markerNodes[i].getAttribute("address");
         var distance = parseFloat(markerNodes[i].getAttribute("distance"));
         var latlng = new google.maps.LatLng(
              parseFloat(markerNodes[i].getAttribute("lat")),
              parseFloat(markerNodes[i].getAttribute("lng")));

         createOption(name, distance, i);
         createMarker(latlng, name, address);
         bounds.extend(latlng);
       }
       map.fitBounds(bounds);
       locationSelect.style.visibility = "visible";
       locationSelect.onchange = function() {
         var markerNum = locationSelect.options[locationSelect.selectedIndex].value;
         // console.log(markers[markerNum]);
         google.maps.event.trigger(markers[markerNum], 'click');
         var departure = $.trim($('#addressInput').val());
         if(departure == '') {
           // departure = getLocationLatLng();
           alert('location input is empty');
           return false;
         }
         calculateAndDisplayRoute(directionsService, directionsDisplay, departure, markers[markerNum].getPosition());
         
       };
      });
    }
    
    function calculateAndDisplayRoute(directionsService, directionsDisplay, start, end) {
      directionsService.route({
        origin: start,
        destination: end,
        travelMode: google.maps.TravelMode.DRIVING
      }, function(response, status) {
        if (status === google.maps.DirectionsStatus.OK) {
          directionsDisplay.setDirections(response);
        } else {
          window.alert('Directions request failed due to ' + status);
        }
      });
    }
    
    function createMarker(latlng, name, address) {
      var html = "<b>" + name + "</b> <br/>" + address;
      var marker = new google.maps.Marker({
        map: map,
        position: latlng
      });
      google.maps.event.addListener(marker, 'click', function() {
        infoWindow.setContent(html);
        infoWindow.open(map, marker);
      });
      markers.push(marker);
    }

    function createOption(name, distance, num) {
      var option = document.createElement("option");
      option.value = num;
      option.innerHTML = name + "(" + distance.toFixed(1) + ")";
      locationSelect.appendChild(option);
    }

    function downloadUrl(url, callback) {
      var request = window.ActiveXObject ?
          new ActiveXObject('Microsoft.XMLHTTP') :
          new XMLHttpRequest;

      request.onreadystatechange = function() {
        if (request.readyState == 4) {
          request.onreadystatechange = doNothing;
          callback(request.responseText, request.status);
        }
      };

      request.open('GET', url, true);
      request.send(null);
    }

    function parseXml(str) {
      if (window.ActiveXObject) {
        var doc = new ActiveXObject('Microsoft.XMLDOM');
        doc.loadXML(str);
        return doc;
      } else if (window.DOMParser) {
        return (new DOMParser).parseFromString(str, 'text/xml');
      }
    }

    function doNothing() {}
    
    function searchReverseGeocoding(lat,lng) {
      var latlng = {lat: lat, lng: lng};
      var geocoder = new google.maps.Geocoder();
      geocoder.geocode({'location': latlng}, function(results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
          if (results[0]) {
            // console.log(results);
            // map.setZoom(11);
            
            /*
            var mymarker = new google.maps.Marker({
              position: latlng,
              map: map
            });
            
            var myinfowindow = new google.maps.InfoWindow;
            myinfowindow.setContent(results[0].formatted_address);
            myinfowindow.open(map, mymarker);
            */
            
            $('#addressInput').val(results[0].formatted_address);
            
            searchLocationsNear(results[0].geometry.location);
            
          } else {
            window.alert('No results found');
          }
        } else {
          window.alert('Geocoder failed due to: ' + status);
        }
      });
    }
    
    function getLocation() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPosition);
      }
    }
    
    function showPosition(position) {
      // console.log(position.coords.latitude +' ' + position.coords.longitude);
      searchReverseGeocoding(position.coords.latitude,position.coords.longitude);
    }
    
    function getLocationLatLng(){
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPositionLatLng);
      }
    }
    
    function showPositionLatLng(position){
      var lat = position.coords.latitude;
      var lng = position.coords.longitude;
      var latlng = {lat: lat, lng: lng};
      return latlng;
    }
    
    $(document).ready(function(){
      $('#addressInput').val('');
      
      $('#gmap-form').bind('submit',function(){ 
        searchLocations(); 
        return false;
      });
      
      $('#radiusSelect, #limitSelect').bind('change',function(){
        if($.trim($('#addressInput').val()) != '') $('#gmap-form').trigger('submit');
        return false;
      });
      
      $('#search-myposition').bind('click',function(){
        getLocation();
      });
    });
    
    
    
  //]]>
  </script>

</head>
<body onload="load();">
	

  <div>
    <form method="post" action="" name="gmap_form" id="gmap-form">
      <input type="text" id="addressInput" size="70" value=""/>
      <select id="radiusSelect">
        <option value="25" selected="selected">25mi</option>
        <option value="100">100mi</option>
        <option value="200">200mi</option>
        <option value="1000">1000mi</option>
        <option value="2000">2000mi</option>
        <option value="3000">3000mi</option>
        <option value="4000">4000mi</option>
        <option value="5000">5000mi</option>
        <option value="10000">10000mi</option>
        <option value="100000">100000mi</option>
      </select>
      <select id="limitSelect">
        <option value="" selected="selected">display all</option>
        <option>5</option>
        <option>10</option>
        <option>20</option>
        <option>30</option>
        <option>40</option>
        <option>50</option>
        <option>100</option>
        <option>150</option>
      </select>
      <input type="submit" value="Search"/>
      <input type="button" value="My Position" id="search-myposition"/>
      <input type="reset" value="Reset" id="search-reset"/>
    </form>
  </div>
  <div><select id="locationSelect" style="width:100%;visibility:hidden"></select></div>
  <div id="directions"></div>
  <div id="map" style="width: 100%; height: 80%"></div>
  
</body>
</html>