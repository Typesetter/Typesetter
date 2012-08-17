	var directionDisplay;
	var directionsService = new google.maps.DirectionsService();
	var map;

	$(function(){
		directionsDisplay = new google.maps.DirectionsRenderer();
		var chicago = new google.maps.LatLng(50.903315,13.67583);
		var myOptions = {
			zoom:17,
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			center: chicago
		}
		map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
		directionsDisplay.setMap(map);
		directionsDisplay.setPanel(document.getElementById("directionsPanel"));
		var chicago = new google.maps.Marker({
			position: chicago,
			map: map,
			title:"Sportpark Dippoldiswalde",
			zIndex: 1
		});

		$('#calc_route_button').click(calcRoute);

	});


	function calcRoute(){
		var start = document.getElementById("map_address").value;
		var request = {
			origin:start,
			destination:"50.903315,13.67583",
			travelMode: google.maps.DirectionsTravelMode.DRIVING
		};
		directionsService.route(request, function(response, status){
			if (status == google.maps.DirectionsStatus.OK){
				directionsDisplay.setDirections(response);
			}
		});
	}
