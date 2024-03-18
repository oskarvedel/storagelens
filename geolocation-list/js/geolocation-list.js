// Get the current URL
function toggleList(geolocationId) {
  console.log(geolocationId);
  // console.log("Toggling fold");
  var formdiv = document.getElementById("formdiv-" + unitId);
  var continue_button = document.getElementById("continue-button-" + unitId);
  // console.log("continue button clicked");
  if (formdiv.style.maxHeight === "0px") {
    // console.log("Opening form");
    formdiv.style.maxHeight = "500px";
    formdiv.style.paddingTop = "1rem";
    formdiv.style.paddingBottom = "1rem";
    continue_button.style.backgroundColor = "#eaeaea";
    // remove the hover effect on .depotrum-row
    var style = document.createElement("style");
    style.innerHTML = `
    .depotrum-list .depotrum-row.partner.yellowhover:hover {
        background-color: #ffffff !important;
      }
    `;
    document.head.appendChild(style);
  } else {
    formdiv.style.maxHeight = "0px";
    formdiv.style.paddingTop = "0rem";
    formdiv.style.paddingBottom = "0rem";
    continue_button.style.backgroundColor = "#ff336a";
    // remove the no-hover style
    var style = document.getElementById("no-hover");
    if (style) {
      document.head.removeChild(style);
    }
  }
}

document.addEventListener("DOMContentLoaded", function () {
  // console.log("DOM loaded");
});
