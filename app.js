

let map;



// This is the function that creates the map
function initMap() {
    const DEFAULT_ZOOM_LEVEL = 10;

    const zoom = Number(mapData.zoomLevel);
    const MAP_ZOOM_LEVEL = isNaN(zoom) ? DEFAULT_ZOOM_LEVEL : zoom;

    // Set the map center
    let center = new google.maps.LatLng(mapPosCenter.lat, mapPosCenter.lng);
    // Set the map options
    let mapOptions = {
        zoom: MAP_ZOOM_LEVEL,
        center: center
    }

    // Create the map
    map = new google.maps.Map(document.getElementById('map'), mapOptions);

    // Iterate over the routes and create a route for each one
    routesData.forEach(route => {
        let start = new google.maps.LatLng(route.start.lat, route.start.lng);
        let finish = new google.maps.LatLng(route.finish.lat, route.finish.lng);
        let color = route.color;
        let icon = route.icon;
        let icon_start = route.icon_start;
        let icon_finish = route.icon_finish;
        let waypoints = route.stops.map(stop => {
            return {
                location: `${stop.lat}, ${stop.lng}`,
                stopover: true
            };
        });

        calculateRoute(start, finish, color, waypoints, icon, icon_start, icon_finish);
    });
}
function calculateRoute(mapOrigin, mapDestination, color, waypoints, icon, icon_start, icon_finish, markerIconUrl) {
    let request = {
        origin: mapOrigin,
        destination: mapDestination,
        waypoints: waypoints,
        travelMode: 'DRIVING',
    };

    // Call the directions service
    new google.maps.DirectionsService().route(request, function (result, status) {
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

            // Add a marker for the start of the route
            new google.maps.Marker({
                position: result.routes[0].legs[0].start_location,
                map: map,
                icon: icon_start.url ? {
                    url: icon_start.url, // Use icon.url as the marker icon
                    scaledSize: new google.maps.Size(50, 50) // Change the size of the icon
                } : {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10
                }
            });


            // Add a marker for each waypoint
            waypoints.forEach(waypoint => {
                new google.maps.Marker({
                    position: new google.maps.LatLng(parseFloat(waypoint.location.split(", ")[0]), parseFloat(waypoint.location.split(", ")[1])),
                    map: map,
                    icon: icon.url ? {
                        url: icon.url, // Use icon.url as the marker icon
                        scaledSize: new google.maps.Size(50, 50) // Change the size of the icon
                    } : {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10
                    }

                });
            });

            // Add a marker for the end of the route
            new google.maps.Marker({
                position: result.routes[0].legs[result.routes[0].legs.length - 1].end_location,
                map: map,
                icon: icon_finish.url ? {
                    url: icon_finish.url, // Use icon.url as the marker icon
                    scaledSize: new google.maps.Size(50, 50) // Change the size of the icon
                } : {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10
                }
            });
        }
    });
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
    // Prevent the form from submitting normally
    event.preventDefault();

    // Get the user's location from the form
    var userLocation = document.getElementById('user-location').value;

    // Geocode the user's location
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({ 'address': userLocation }, function (results, status) {
        if (status == 'OK') {
            var userCoords = {
                lat: results[0].geometry.location.lat(),
                lng: results[0].geometry.location.lng()
            };

            // Iterate over the routes and find the closest waypoint for each one
            routesData.forEach(route => {
                let waypoints = route.stops.map(stop => {
                    return {
                        location: `${stop.lat}, ${stop.lng}`,
                        stopover: true
                    };
                });

                var closestWaypoint = findClosestWaypoint(userCoords, waypoints);
                // Log the closestWaypoint object
                console.log('closestWaypoint:', closestWaypoint);
                if (closestWaypoint && closestWaypoint.location) {
                    var [lat, lng] = closestWaypoint.location.split(',').map(Number);
                    if (typeof lat === 'number' && typeof lng === 'number') {
                        var waypointLatLng = new google.maps.LatLng(lat, lng);
                        map.setCenter(waypointLatLng);
                        map.setZoom(16);
                    } else {
                        console.error('Invalid coordinates:', closestWaypoint);
                    }
                } else {
                    console.error('No closest waypoint found');
                }
            });
        } else {
            alert('Geocode was not successful for the following reason: ' + status);
        }
    });
});
function findClosestWaypoint(userCoords, waypoints) {
    var allWaypoints = waypoints.concat(waypoints);
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