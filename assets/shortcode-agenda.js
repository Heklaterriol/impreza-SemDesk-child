/**
 * Styles for agenda shortcode/widget.
 * uses library: https://splidejs.com
 * 
 * @package HelloIVY
*/

const splideCurrent = new Splide( '#widget-current .splide', {
	direction: 'ttb',
	height: '400px',
	// fixedHeight: '110px',
	autoHeight: true,
	// perPage: '4',
	gap: '5px',
	perPage: 3,
	perMove: 1,
	lazyLoad: 'nearby',
	autoWidth: true,
	pagination: false,
	rewind: false,
	// wheel: true,
	arrows: true,
	breakpoints: {
		767: {
			arrows: false,
			pagination: false,
			perPage: 999,
			drag: false,
		},
	}
} );
splideCurrent.mount();

const splideUpcoming = new Splide( '#widget-upcoming .splide', {
	perPage: 3,
	perMove: 1,
	flickMaxPages: 1,
	gap: '30px',
	rewind : false,
	lazyLoad: 'nearby',
	pagination: true,
	height:'450px',
	dragMinThreshold: 20, // https://splidejs.com/guides/options/#dragminthreshold
	breakpoints: {
		1220:{
			perPage: 2,
		},
		910: {
			perPage: 2,
		},
		630: {
			perPage: 1,
			pagination: false,
		},
	}
} );
splideUpcoming.mount();