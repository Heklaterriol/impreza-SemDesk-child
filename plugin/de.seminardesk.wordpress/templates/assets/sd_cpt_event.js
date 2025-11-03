/**
 * JavaScript for single template of CPT sd_cpt_event
 * 
 * @package SeminardeskPlugin
 */

// get the modal and its content
var modal = document.querySelector(".sd-modal");
var btnBooking = document.querySelector(".sd-modal-booking-btn");
// get button that closes the modal
var btnClose = document.querySelector(".sd-modal-close-btn");

// toggle between show and hide of the modal
function sdModalToggle() {
	modal.classList.toggle("sd-modal-show");
}
function windowOnClick(event) {
	if (event.target === modal) {
		sdModalToggle();
	}
}

// click events listener
if ( btnBooking !== null && modal !== null ){
	btnBooking.addEventListener("click", sdModalToggle);
	btnClose.addEventListener("click", sdModalToggle);
	window.addEventListener("click", windowOnClick);
}

