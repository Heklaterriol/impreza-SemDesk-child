/**
 * Script for single template of the event detail page.
 * 
 * @package HelloIVY
 */

/**
 * BEGIN Multi-Modal
 * 
 * Source: https://stackoverflow.com/a/40645236
 */

// Get the button that opens the modal
const buttons = document.querySelectorAll('button.modal-button');

// All page modals
const modals = document.querySelectorAll('.modal');

// Get the <span> element that closes the modal
const spans = document.querySelectorAll('span.close');

// the body
const body = document.body;

// When the user clicks the button, open the modal and fetch the iframe
buttons.forEach((button) => {
	button.onclick = function(event) {
		// event.preventDefault();
		modal = document.querySelector(event.target.getAttribute('href'));
		let iframe = modal.querySelector('.iframe-sd-booking');
		if (iframe.getAttribute('data-src') && !iframe.getAttribute('src')){ // only do it once per iframe
			iframe.setAttribute('src', iframe.getAttribute('data-src'));
		}
		modal.style.display = 'block';
		body.style.overflowY = 'hidden';
	}
});

// When the user clicks on <span> (x), close the modal
spans.forEach((span) => {
	span.onclick = function(event) {
		event.preventDefault();
		modals.forEach((modal) => {
			modal.style.display = 'none';
			body.style.overflowY = 'auto';
		});
	}
});

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
	if (event.target.classList.contains('modal')) {
		modals.forEach((modal) => {
			modal.style.display = 'none';
			body.style.overflowY = 'auto';
		})
	}
}

/**
 * End Multi-Modal 
 */

/**
 * BEGIN Read-More
 */

const readMoreButtons = document.querySelectorAll('.read-more');
const readMoreTexts = document.querySelectorAll('.read-more .text');
const textBoxes = document.querySelectorAll('.text-box');
const anglesRight = document.querySelectorAll('i.fa-angle-right');
const anglesDown = document.querySelectorAll('i.fa-angle-down');

readMoreButtons.forEach((readMoreButton, index) => {
  const textBox = textBoxes[index];
	const angleRight = anglesRight[index];
	const angleDown = anglesDown[index];
	const readMoreText = readMoreTexts[index];
	
  readMoreButton.addEventListener('click', () => {
		if (angleRight.style.display === "none") {
			angleRight.style.display = "block";
			angleDown.style.display = "none";
		} else {
			angleRight.style.display = "none";
			angleDown.style.display = "block";
		}
		angleRight.style.display.toggle;
		angleDown.style.display.toggle;
    textBox.classList.toggle('expanded');
    if (textBox.classList.contains('expanded')) {
      readMoreText.textContent = 'Lire moins...';
    } else {
      readMoreText.textContent = 'Lire la suite...';
    }
  });
});

/**
 * END Read-More
 */