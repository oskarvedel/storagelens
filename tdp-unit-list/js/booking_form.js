// Get the current URL
const currentUrl = window.location.href;

// Define the base URL of your subset
const baseUrl = "lokation";

// Check if the current URL starts with the base URL
if (currentUrl.includes(baseUrl)) {
  console.log("Current URL is in the subset");
  populateDates();
  // Add the event listener if the current URL is in the subset
  document.addEventListener("click", function (e) {
    const select = document.querySelector(".custom-select");
    if (select && !select.contains(e.target)) {
      select.classList.remove("open");
    }
  });

  const trigger = document.querySelector(".custom-select__trigger");
  if (trigger) {
    trigger.addEventListener("click", function () {
      this.parentElement.classList.toggle("open");
    });
  }
}

function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

function populateDates() {
  console.log("Populating dates");
  let containers = document.querySelectorAll(".custom-select__trigger");

  containers.forEach((optionsContainer) => {
    // const optionsContainer = document.querySelector(".custom-select__trigger");
    const selectDateOption = document.createElement("option");
    selectDateOption.textContent = "Vælg en dato";
    selectDateOption.disabled = true;
    selectDateOption.selected = true; // The option is selected by default
    optionsContainer.appendChild(selectDateOption);

    for (let i = 0; i < 20; i++) {
      const date = new Date();
      date.setDate(date.getDate() + i);
      const option = document.createElement("option");
      option.className = "date";
      let dateString = date.toLocaleDateString("da-DK", {
        weekday: "long",
        month: "long",
        day: "numeric",
      });
      option.textContent = capitalizeFirstLetter(dateString);
      var isoDate = date.toISOString();
      option.dataset.value = isoDate;

      optionsContainer.appendChild(option);

      // Check if the current day is Saturday (6)
      if (date.getDay() === 6) {
        // Create and append the separator
        const separator = document.createElement("option");
        separator.disabled = true; // Disable the separator so it can't be selected
        separator.textContent = "----------";
        optionsContainer.appendChild(separator);
      }
    }
    // Check if the last child is a separator, add one if it isn't
    const lastChild = optionsContainer.lastChild;
    if (!lastChild.textContent === "----------") {
      const separator = document.createElement("option");
      separator.disabled = true; // Disable the separator so it can't be selected
      separator.textContent = "----------";
      optionsContainer.appendChild(separator);
    }

    const futureDateOption = document.createElement("option");
    futureDateOption.textContent = "Fremtidig Dato";
    futureDateOption.dataset.value = "future";
    optionsContainer.appendChild(futureDateOption);
  });
}

document.addEventListener("DOMContentLoaded", function () {
  var forms = document.querySelectorAll(".booking_form");

  forms.forEach((form) => {
    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var dateSelected = true;

      //get the date_dropdown in this instance of the booking_form
      var date_dropdown = form.querySelector(".custom-select__trigger");
      if (date_dropdown.value === "Vælg en dato") {
        event.preventDefault();
        console.log("unhid error message");
        var date_error_message = form.querySelector(".error-message");
        date_error_message.style.display = "block";
        dateSelected = false;
      }

      if (!dateSelected) return;

      var formData = new FormData(form);

      $unit_id = formData.get("unit_id");

      console.log("Form submitted with data: ", formData);

      var enable_direct_booking = formData.get("enable_direct_booking") === "1";

      console.log("Enable direct booking: ", enable_direct_booking);

      if (enable_direct_booking) {
        var booking_link = formData.get("booking_link");
      }

      var xhr = new XMLHttpRequest();
      xhr.open("POST", "/wp-admin/admin-ajax.php", true);
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
      formData.append("action", "booking_form_action"); // Action hook name

      xhr.onload = function () {
        if (this.status >= 200 && this.status < 400) {
          // Handle the response from the php form handler
          var resp = JSON.parse(this.response);
          if (resp.success === true) {
            // The PHP script returned success
            if (enable_direct_booking) {
              if (booking_link) {
                // console.log("Redirecting to booking link");
                window.location.href = booking_link;
                return;
              } else {
                var errorXhr = new XMLHttpRequest();
                errorXhr.open("POST", "/wp-admin/admin-ajax.php", true);
                errorXhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

                var errorFormData = new FormData();
                errorFormData.append("error_message", "no_booking_link");
                errorFormData.append("unit_id", formData.get("unit_id"));
                errorFormData.append("action", "javascript_error_action"); // Action hook name

                errorXhr.send(errorFormData);

                window.location.href =
                  "/reservation/confirmation/" + resp.booking_id;
              }
            } else {
              console.log(
                "Direct booking disabled, redirecting to thank you page"
              );
              window.location.href =
                "/reservation/confirmation/" + resp.booking_id;
            }
          }
        } else {
          var errorXhr = new XMLHttpRequest();
          errorXhr.open("POST", "/wp-admin/admin-ajax.php", true);
          errorXhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

          var errorFormData = new FormData();
          errorFormData.append("error_message", "undefined_booking_error");
          errorFormData.append("unit_id", formData.get("unit_id"));
          errorFormData.append("action", "javascript_error_action"); // Action hook name

          errorXhr.send(errorFormData);
        }
      };
      xhr.send(formData);
    });
  });
});

function toggleFold(unitId) {
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
  var booking_forms = document.querySelectorAll(".booking_form");
  var submitButtons = document.querySelectorAll(".booking_form input[type='submit']");

  booking_forms.forEach((booking_form) => {
    booking_form.addEventListener("submit", function () {

      // Disable all submit buttons
      submitButtons.forEach((button) => {
        button.disabled = true;
        button.value = "Arbejder...";
        button.style.backgroundColor = "#B2B2B2";
      });
    });
  });
});


document.addEventListener("DOMContentLoaded", function () {
  var date_dropdowns = document.querySelectorAll(".custom-select__trigger");

  date_dropdowns.forEach((date_dropdown) => {
    date_dropdown.addEventListener("change", function () {
      // console.log("Date dropdown changed");
      var selectedOption = this.options[this.selectedIndex];

      const move_in_date_value = selectedOption.dataset.value;

      // console.log(move_in_date_value);

      var move_in_dates = document.querySelectorAll(".move_in_date");

      // console.log(move_in_dates);

      move_in_dates.forEach((move_in_date) => {
        move_in_date.value = move_in_date_value;
      });
    });
  });
});
