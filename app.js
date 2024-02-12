

let map;



// This is the function that creates the map
function initMap() {
    const RED_ROUTE_COLOR = '#BE1E2D';
    const BLUE_ROUTE_COLOR = '#0033CC';
    const RED_ROUTE_ICON = 'http://framework.local/wp-content/uploads/2024/02/busRouteRed.svg';
    const BLUE_ROUTE_ICON = 'http://framework.local/wp-content/uploads/2024/02/busRouteBlue.svg';
    const SCHOOL_ICON = "http://framework.local/wp-content/uploads/2024/02/schoolPin.svg";
    const MARKER_SIZE = new google.maps.Size(25, 25);
    const DEFAULT_ZOOM_LEVEL = 10;

    const zoom = Number(mapData.zoomLevel);
    const MAP_ZOOM_LEVEL = isNaN(zoom) ? DEFAULT_ZOOM_LEVEL : zoom;


    let directionsService = new google.maps.DirectionsService();
    // Set the map center
    let center = new google.maps.LatLng(mapPosCenter.lat, mapPosCenter.lng);
    let busRoute1 = new google.maps.LatLng(bsr1start.lat, bsr1start.lng);
    let busRoute2 = new google.maps.LatLng(bsr2start.lat, bsr2start.lng);
    // Set the bus route destination coordinates
    let busRouteDestination1 = new google.maps.LatLng(bsr1finish.lat, bsr1finish.lng);
    let busRouteDestination2 = new google.maps.LatLng(bsr2finish.lat, bsr2finish.lng);
    // Set the map options
    let mapOptions = {
        zoom: MAP_ZOOM_LEVEL,
        center: center
    }
    console.log(mapOptions);

    // Create the map
    map = new google.maps.Map(document.getElementById('map'), mapOptions);
    // Call the function to create the route
    function calculateRoute(mapOrigin, mapDestination, color, waypoints, markerIconUrl) {
        let request = {
            origin: mapOrigin,
            destination: mapDestination,
            waypoints: waypoints,
            travelMode: 'DRIVING',
        };
        // Call the directions service
        directionsService.route(request, function (result, status) {
            if (status == "OK") {
                let directionsDisplay = new google.maps.DirectionsRenderer({
                    map: map,
                    suppressMarkers: true,
                    polylineOptions: {
                        strokeColor: color, // this is declared at the bottom
                        strokeWeight: 4,
                        strokeOpacity: 1
                    },
                    preserveViewport: true
                });
                directionsDisplay.setDirections(result);
                // Starting location marker
                var startMarker = new google.maps.Marker({
                    position: result.routes[0].legs[0].start_location,
                    map: map,
                    icon: {
                        url: markerIconUrl,
                        scaledSize: MARKER_SIZE // sets size of marker
                    },
                });

                // Waypoint Markers
                var waypointMarkers = result.routes[0].legs.slice(0, -1).map(function (leg, index) {
                    return new google.maps.Marker({
                        position: leg.end_location,
                        map: map,
                        icon: {
                            url: markerIconUrl,
                            scaledSize: MARKER_SIZE // sets size of marker
                        },
                    });
                });
                // Final Destination Marker
                var endMarker = new google.maps.Marker({
                    position: result.routes[0].legs[result.routes[0].legs.length - 1].end_location,
                    map: map,
                    icon: {
                        url: SCHOOL_ICON,
                        scaledSize: MARKER_SIZE // sets size of marker
                    },
                });
            }
        });
    }
    // Red Route Waypoints
    let waypoints = busRoute1Stops.map(stop => {
        return {
            location: `${stop.lat}, ${stop.lng}`,
            stopover: true
        };
    });
    // Blue Route Waypoints
    let waypoints2 = busRoute2Stops.map(stop => {
        return {
            location: `${stop.lat}, ${stop.lng}`,
            stopover: true
        };
    });

    // Call the function for each route
    calculateRoute(busRoute1, busRouteDestination1, RED_ROUTE_COLOR, waypoints, RED_ROUTE_ICON);
    calculateRoute(busRoute2, busRouteDestination2, BLUE_ROUTE_COLOR, waypoints2, BLUE_ROUTE_ICON);
}
function haversineDistance(coords1, coords2) {
    function toRad(x) {
        return x * Math.PI / 180;
    }

    var lon1 = coords1.lng;
    var lat1 = coords1.lat;

    var lon2 = coords2.lng;
    var lat2 = coords2.lat;

    var R = 6371; // km

    var x1 = lat2 - lat1;
    var dLat = toRad(x1);
    var x2 = lon2 - lon1;
    var dLon = toRad(x2)
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    var d = R * c;

    return d;
}

document.getElementById('location-form').addEventListener('submit', function (event) {
    function setMapCenter(map, latLng) {
        map.setCenter(latLng);
    }
    event.preventDefault();
    var userLocation = document.getElementById('user-location').value;
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({ 'address': userLocation }, function (results, status) {
        if (status == 'OK') {
            var userCoords = {
                lat: results[0].geometry.location.lat(),
                lng: results[0].geometry.location.lng()
            };
            // Red Route Waypoints
            let waypoints = busRoute1Stops.map(stop => {
                return {
                    location: `${stop.lat}, ${stop.lng}`,
                    stopover: true
                };
            });
            // Blue Route Waypoints
            let waypoints2 = busRoute2Stops.map(stop => {
                return {
                    location: `${stop.lat}, ${stop.lng}`,
                    stopover: true
                };
            });
            console.log('userCoords:', userCoords);
            var closestWaypoint = findClosestWaypoint(userCoords, waypoints, waypoints2);
            // Log the closestWaypoint object
            console.log('closestWaypoint:', closestWaypoint);
            if (closestWaypoint && closestWaypoint.location) {
                var [lat, lng] = closestWaypoint.location.split(',').map(Number);
                if (typeof lat === 'number' && typeof lng === 'number') {
                    var waypointLatLng = new google.maps.LatLng(lat, lng);
                    setMapCenter(map, waypointLatLng);
                    map.setZoom(16);
                } else {
                    console.error('Invalid coordinates:', closestWaypoint);
                }
            } else {
                console.error('No closest waypoint found');
            }

        } else {
            alert('Geocode was not successful for the following reason: ' + status);
        }
    });
});
function findClosestWaypoint(userCoords, waypoints, waypoints2) {
    var allWaypoints = waypoints.concat(waypoints2);
    var closestWaypoint;
    var shortestDistance;
    allWaypoints.forEach(function (waypoint) {
        var [lat, lng] = waypoint.location.split(',').map(Number);
        var waypointCoords = {
            lat: lat,
            lng: lng
        };
        var distance = haversineDistance(userCoords, waypointCoords);
        if (shortestDistance === undefined || distance < shortestDistance) {
            shortestDistance = distance;
            closestWaypoint = waypoint;
        }
    });
    return closestWaypoint;
}