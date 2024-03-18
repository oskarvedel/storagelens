// Get the current URL
function toggleList(geolocationId) {
  console.log(geolocationId);

  // get the geolocations from the local storage
  var geolocations = JSON.parse(
    localStorage.getItem("frontend-geolocations-array")
  );

  // Select the parent div
  var parentDiv = document.querySelector(
    ".ep-location-navigator__location-list"
  );

  // Clear the parent div
  parentDiv.innerHTML = "";

  //find the array in geolocations that matches the geolocationId
  var locations_for_geolocationId = geolocations[geolocationId];
  console.log(locations_for_geolocationId);

  // Iterate over the geolocations array
  locations_for_geolocationId.forEach(function (geolocation) {
    // Create a new div
    var newDiv = document.createElement("div");
    newDiv.className = "ep-location-navigator__location-link";
    newDiv.dataset.name = geolocation.name;
    newDiv.dataset.units = geolocation.seo_num_of_units_available;
    // newDiv.onclick = function () {
    //   toggleList(geolocation.id);
    // };

    // Create a new p element
    var newP = document.createElement("div");
    newP.textContent =
      geolocation.name +
      " (" +
      geolocation.seo_num_of_units_available +
      " ledige depotrum)";

    // Append the new p element to the new div
    newDiv.appendChild(newP);

    // Append the new div to the parent div
    parentDiv.appendChild(newDiv);
  });
}

// check if the page is "tjekdepot.dk" or "tjekdepot.dk/lokation"
if (currentUrl.includes("tjekdepot") || currentUrl.includes("lokation")) {
  console.log("setting frontend geolocations array");
  fetch(theme.uri + "/frontend-geolocations-array.json")
    .then((response) => response.text())
    .then((data) => {
      // console.log(data);
      var geolocations = JSON.parse(data);
      // save geocations to local storage
      localStorage.setItem(
        "frontend-geolocations-array",
        JSON.stringify(geolocations)
      );
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}
