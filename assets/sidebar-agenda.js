/**
 * Script for agenda sidebar, taxonomy sd_txn_dates with upcoming event dates.
 * 
 * @package HelloIVY
*/

/**
 * constant variables
 */

const queryString = window.location.search;
const urlParams = new URLSearchParams(queryString);

/**
 * BEGIN utils
 */

/**
 * BEGIN searchbar and selectors (filters)
 */

const eleDate = document.getElementById('select-sd-date');
const eleCategory = document.getElementById('select-sd-category');
const eleLevel = document.getElementById('select-sd-level');
const eleSearchInput = document.getElementById('ivy-search-input');

function onclickSearch(){
	if ( eleSearchInput.value != '' ){
		console.log('onSearch'); 
		eleDate.style.backgroundColor = "#eceeef";
		eleLevel.style.backgroundColor = "#eceeef";
		eleCategory.style.backgroundColor = "#eceeef";
	}
}

if (urlParams.has('date')) {
	eleDate.value = urlParams.get('date');
	eleDate.style.backgroundColor = "#9cc1ba";
}

if (urlParams.has('level')) {
	eleLevel.value = urlParams.get('level');
	eleLevel.style.backgroundColor = "#9cc1ba";
}

if (urlParams.has('category')) {
	eleCategory.value = urlParams.get('category');
	eleCategory.style.backgroundColor = "#9cc1ba";
}

function onchangeSelect(ele){
	eleSearchInput.style.backgroundColor = "#eceeef";
	eleSearchInput.value = "";
	if (ele.value && ele.value != "all"){
		let value = ele.value;
		window.location.href="?" + ele.name + "=" + ele.value + "#content";
		eleDate.style.backgroundColor = "#eceeef";
		eleLevel.style.backgroundColor = "#eceeef";
		eleCategory.style.backgroundColor = "#eceeef";
		eleDate.value = "all";
		eleLevel.value = "all";
		eleCategory.value = "all";
		ele.value = value;
		ele.style.backgroundColor = "#9cc1ba";
	}else{
		window.location.href= window.location.origin + window.location.pathname + "#content";
		// window.location.href= WPVARS.href + "#content";
		eleDate.value = "all";
		eleLevel.value = "all";
		eleCategory.value = "all";
		eleDate.style.backgroundColor = "#eceeef";
		eleLevel.style.backgroundColor = "#eceeef";
		eleCategory.style.backgroundColor = "#eceeef";
	}
}

/**
 * BEGIN attendance type visibility
 */

var cboxOnsite = document.getElementById("cbox-sd-onsite");
var cboxOnline = document.getElementById("cbox-sd-online");
// const cbox_onsite = document.querySelector(".cbox-online input")

if (urlParams.has('onsite')) {
	if (urlParams.get('onsite') == 'false' || urlParams.get('onsite') == '0') {
		cboxOnsite.checked = false;
		onclickCbox();
	} else {
		cboxOnsite.checked = true;
		onclickCbox();
	}
} else {
	cboxOnsite.checked = true;
	onclickCbox();
}

if (urlParams.has('online')) {
	if (urlParams.get('online') == 'false' || urlParams.get('online') == '0') {
		cboxOnline.checked = false;
		onclickCbox()
	} else {
		cboxOnline.checked = true;
		onclickCbox()
	}
} else {
	cboxOnline.checked = true;
	onclickCbox()
}

function onclickCbox() {
	const datesOnsite = document.querySelectorAll(".sd-date-onsite");
	const datesOnline = document.querySelectorAll(".sd-date-online");
	if (cboxOnsite.checked == true && cboxOnline.checked == true) {
		// dates online onsite visible
		datesOnsite.forEach((date) => {
			date.style.display = "block";
		})
		datesOnline.forEach((date) => {
			date.style.display = "block";
		})
	} else if (cboxOnline.checked == true) {
		// dates onsite not visible, dates online visible
		datesOnsite.forEach((date) => {
			date.style.display = "none";
		})
		datesOnline.forEach((date) => {
			date.style.display = "block";
		})
	} else if (cboxOnsite.checked == true) {
		// dates online not visible, dates online visible
		datesOnline.forEach((date) => {
			date.style.display = "none";
		})
		datesOnsite.forEach((date) => {
			date.style.display = "block";
		})
	} else {
		// all dates not visible
		datesOnsite.forEach((date) => {
			date.style.display = "none";
		})
		datesOnline.forEach((date) => {
			date.style.display = "none";
		})
	}
	containerVisibility();
}

function containerVisibility() {
	const monthContainers = document.querySelectorAll(".sd-month-container");
	const noResult = document.querySelector(".no-result");
	monthContainers.forEach((month) => {
		const dates = month.childNodes;
		// set month container invisible and no-result container visible
		month.style.display = "none";
		noResult.style.display = "block";
		// if month container holds at least on date set month container visible and no-result container invisible
		dates.forEach((date) => {
			if (typeof date.style !== "undefined" && date.style.display === "block") {
				month.style.display = "block";
				noResult.style.display = "none";
				return;
			}
		})
	})
}

containerVisibility();