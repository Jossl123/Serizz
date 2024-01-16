var dateRange = document.getElementById("date-range-span");
var dateMin = document.getElementById("date-min");
var dateMax = document.getElementById("date-max");
var dateTextMin = document.getElementById("date-text-min");
var dateTextMax = document.getElementById("date-text-max");

var dateMinValue = dateMin.value;
var dateMaxValue = dateMax.value;
var dateRangeWidth = dateRange.offsetWidth;
var diff = dateMaxValue - dateMinValue;
dateMin.oninput = function () {
    if (parseInt(dateMin.value) >= parseInt(dateMax.value)) {
        dateMax.value = dateMin.value;
    }
    var dateRangeValue = dateRangeWidth / (dateMaxValue - dateMinValue);
    dateRange.style.width =
        dateRangeValue * (dateMax.value - dateMin.value) + "px";
    dateRange.style.left = (dateMin.value - dateMinValue) * dateRangeValue + "px";

    dateTextMin.style.left =
        (dateMin.value - dateMinValue) * dateRangeValue + "px";
    dateTextMin.innerHTML = dateMin.value;
    dateTextMax.style.right =
        (dateMaxValue - dateMax.value) * dateRangeValue + "px";
    dateTextMax.innerHTML = dateMax.value;
};
dateMax.oninput = function () {
    if (parseInt(dateMax.value) <= parseInt(dateMin.value)) {
        dateMin.value = dateMax.value;
    }
    var dateRangeValue = dateRangeWidth / (dateMaxValue - dateMinValue);
    dateRange.style.width =
        dateRangeValue * (dateMax.value - dateMin.value) + "px";
    dateRange.style.left = (dateMin.value - dateMinValue) * dateRangeValue + "px";

    dateTextMin.style.left =
        (dateMin.value - dateMinValue) * dateRangeValue + "px";
    dateTextMin.innerHTML = dateMin.value;
    dateTextMax.style.right =
        (dateMaxValue - dateMax.value) * dateRangeValue + "px";
    dateTextMax.innerHTML = dateMax.value;
};

function formSubmit(e) {
    e.preventDefault();
    const form = document.querySelector("form");
    var checkboxes = document.querySelectorAll(".genre");
    var genres = [];
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
        genres.push(checkboxes[i].id);
        }
    }

    var input = document.createElement("input");
    input.setAttribute("name", "genre");
    input.setAttribute("value", genres.join("_"));

    form.appendChild(input);

    form.submit();
    
}


//rate search range

var rateMin = document.getElementById("rate-min");
var rateMax = document.getElementById("rate-max");

var stars_yellow = []
for (let i = 0; i < 5; i++) {
    stars_yellow.push(document.getElementById("rate-star-yellow-"+i))
}
var stars_grey = []
for (let i = 0; i < 5; i++) {
    stars_grey.push(document.getElementById("rate-star-grey-"+i))
}

var rateMinValue = rateMin.value;
var rateMaxValue = rateMax.value;

rateMin.oninput = function () {
    if (parseInt(rateMin.value) >= parseInt(rateMax.value)) {
        rateMax.value = rateMin.value;
    }
    updateStars()
};

rateMax.oninput = function () {
    if (parseInt(rateMax.value) <= parseInt(rateMin.value)) {
        rateMin.value = rateMax.value;
    }
    updateStars()
};

function updateStars() {
    var min = rateMin.value / 2;
    var max = rateMax.value / 2;
    for (let i = 0; i < stars_grey.length; i++) {
        var start_grad_max = parseInt(Math.max(0, Math.min(max - i, 1)) * 100);
        var start_grad_min = parseInt(Math.max(0, Math.min(min - i, 1)) * 100);
        
        stars_yellow[i].style.WebkitMask = `linear-gradient(to right, rgba(0, 0, 0, 1) ${start_grad_max}%, rgba(0, 0, 0, 0) ${start_grad_max}%)`;
        stars_grey[i].style.WebkitMask = `linear-gradient(to right, rgba(0, 0, 0, 0) ${start_grad_max}%, rgba(0, 0, 0, 1) ${start_grad_max}%)`;
        stars_yellow[i].style.WebkitMask = `linear-gradient(to right, rgba(0, 0, 0, 0) ${start_grad_min}%, rgba(0, 0, 0, 1) ${start_grad_min}%,rgba(0, 0, 0, 1) ${start_grad_max}%, rgba(0, 0, 0, 0) ${start_grad_max}%)`;
        stars_grey[i].style.WebkitMask = `linear-gradient(to right, rgba(0, 0, 0, 1) ${start_grad_min}%, rgba(0, 0, 0, 0) ${start_grad_min}%, rgba(0, 0, 0, 0) ${start_grad_max}%, rgba(0, 0, 0, 1) ${start_grad_max}%)`;
    }
}

