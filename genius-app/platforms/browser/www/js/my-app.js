var $$ = Dom7;
var offSiteRoot = "http://127.0.0.1/fw7electron/membershipCardApp/";
var app = new Framework7({
  // App root element
  root: '#app',
  // App Name
  name: 'iGenius App',
  // App id
  id: 'org.rotaractcolombomidtown.igeniusApp',
  // Enable swipe panel
  panel: {
	swipe: 'left',
  },
  // Add default routes
  routes: [
	{
	  name: 'Home',
	  path: '/',
	  url: 'main.html',
	}
  ],
  // ... other parameters
});

var mainView = app.views.create('.view-main', {url: '/'});


// Option 1. Using one 'page:init' handler for all pages
$$(document).on('page:init', function (e) {
	// Do something here when page loaded and initialized

  	var swiper = app.swiper.create('.swiper-container', {
		speed: 400,
		spaceBetween: 100
	});


	Framework7.request.get(offSiteRoot + 'server/index.php', function (data) {
		console.log(data);
	});

});

// Handle Cordova Device Ready Event
$$(document).on('deviceready', function() {

});

// create searchbar
var searchbar = app.searchbar.create({
  el: '.searchbar',
  searchContainer: '.list',
  searchIn: '.item-title',
  on: {
	search(sb, query, previousQuery) {
	  console.log(query, previousQuery);
	}
  }
})