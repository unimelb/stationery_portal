/*
 * jQuery throttle / debounce - v1.1 - 3/7/2010
 * http://benalman.com/projects/jquery-throttle-debounce-plugin/
 * 
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */
(function(b,c){var $=b.jQuery||b.Cowboy||(b.Cowboy={}),a;$.throttle=a=function(e,f,j,i){var h,d=0;if(typeof f!=="boolean"){i=j;j=f;f=c}function g(){var o=this,m=+new Date()-d,n=arguments;function l(){d=+new Date();j.apply(o,n)}function k(){h=c}if(i&&!h){l()}h&&clearTimeout(h);if(i===c&&m>e){l()}else{if(f!==true){h=setTimeout(i?k:l,i===c?e-m:e)}}}if($.guid){g.guid=j.guid=j.guid||$.guid++}return g};$.debounce=function(d,e,f){return f===c?a(d,e,false):a(d,f,e!==false)}})(this);

$(function() {

/*

      Homepage Banners animation

                                    */
  var homeTimer = false;
  // Hide all banners except the first one
  $('.home-banner').hide().last().show().addClass('current');

  // Only add the banner navigation if there is more than one banner
  if ($('#home-banners .home-banner').length > 1) {
    // Start the banners in a paused state
    $('#home-banners').addClass('paused');
    // Inject the banner navigation
    $('#home-banners').append('<p class="banner-navigation"><a class="previous-banner" href="#">Previous</a><a class="play-pause-banner" href="#">Play/Pause</a><a class="next-banner" href="#">Next</a><span class="banner-count"></span></p>');
    // Initialise banner count
    $('.banner-navigation .banner-count').html('<em>1</em>/'+$('#home-banners .home-banner').length)
    // Update banner count
    var updateBannerCount = function(target) {
      var target = target || '#home-banners .home-banner:visible';
      var count = $(target).index('#home-banners .home-banner');
      if (count > $('#home-banners .home-banner').length) count = 0;
      $('.banner-navigation .banner-count em').html(count + 1);
    }
    // Delegate click events
    $('#home-banners').delegate('.next-banner', 'click', function(e) {
      e.preventDefault();
      clearTimeout(homeTimer);
      var callback = function() {};
      // If the click originates from a real click event (assertained by checking for e.screenX), then pause the banner
      if (e.screenX && !$('#home-banners').is('.paused')) {
        $('#home-banners .play-pause-banner').click();
      } else if (!e.screenX && !$('#home-banners').is('.paused')) {
        // If the click doesn't originate from a real click event, and the slideshow isn't paused, queue up the next slide
        callback = function() { homeTimer = setTimeout(function() { $('#home-banners .next-banner').click(); }, 5000); }
      }
      // Find the currently visible banner
      var current = $('#home-banners .current'),
          next = current.next('.home-banner');
      // If there is no next banner, cycle back to the start
      if (!next.length) next = $('#home-banners .home-banner').first();
      // Fade out the current banner and fade in the new one
      current.removeClass('current').fadeOut(750);
      next.addClass('current').fadeIn(750, callback);
      // Update banner count
      updateBannerCount(next);
    });
    $('#home-banners').delegate('.previous-banner', 'click', function(e) {
      e.preventDefault();
      clearTimeout(homeTimer);
      // If the click originates from a real click event (assertained by checking for e.screenX), then pause the banner
      if (e.screenX && !$('#home-banners').is('.paused')) $('#home-banners .play-pause-banner').click();
      // Find the currently visible banner
      var current = $('#home-banners .current'),
          prev = current.prev('.home-banner');
      // If there is no previous banner, cycle back to the last one
      if (!prev.length) prev = $('#home-banners .home-banner').last();
      // Fade out the current banner and fade in the new one
      current.removeClass('current').fadeOut(750);
      prev.addClass('current').fadeIn(750);
      // Update banner count
      updateBannerCount(prev);
    });
    $('#home-banners').delegate('.play-pause-banner', 'click', function(e) {
      e.preventDefault();
      if ($('#home-banners').is('.paused')) {
        // Play the homepage banners
        $('#home-banners').removeClass('paused');
        $('#home-banners .next-banner').click();
      } else {
        // Pause the homepage banners
        clearTimeout(homeTimer);
        $('#home-banners').addClass('paused');
      }
    });
    // Autoplay the banners
    $('#home-banners .play-pause-banner').click();
  }

/*

      Homepage banner responsive resizing

                                              */
  var ratio = 0,
      wrapper = $('#home-banners'),
      banners = $('.banner', wrapper),
      links = $('ol a', wrapper);

  var resizeBanners = function() {
    var bannerHeight = $('img:visible', wrapper)[0].offsetHeight;
    // The dimensions for all of the banner sub-elements are defined in ems,
    // so we can resize everything in scale by setting the font-size property
    // on the parent
    wrapper.css('font-size', Math.floor(bannerHeight / 1.5) + '%');
    // The wrapper overflow sizes need to be set manually to match the image
    banners.css('height', bannerHeight);
    wrapper.css('height', bannerHeight);
  }

  var imageLoad = function() {
    // Calculate the image ratio (1:3, etc)
    ratio = ((this.offsetHeight + 1) / this.offsetWidth) * 100;
    // Resize banners (the first time)
    resizeBanners();
  }

  // Once the image has loaded, we can reference its width and height
  var image = $('img', wrapper).first();
  // Reset the src to ensure the load event fires
  image.load(imageLoad).attr('src', image.attr('src'));
  // Resize the banners on page resize (throttled to run every 50ms)
  $(window).resize( $.throttle(50, resizeBanners) );

  // // Tab-based navigation
  // $('> a', banners).focus(function() {
  //   // store the index of the focused banner
  //   var index = banners.index(this.parentNode);
  //   // then activate the click event of the corresponding link
  //   links.eq(index).click();
  // });

})