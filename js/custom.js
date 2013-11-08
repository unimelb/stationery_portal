$(document).ready(function() {


// policy box

	$('.policy').each(function() {
		$('p', this).hide().first().show();
	});

	$('.policy').append('<p class="show-hide"><a href="#">Learn more</a></p>');

	$('.policy').delegate('.show-hide a', 'click', function(e) {
		e.preventDefault();
		if ($(this).text() == 'Learn more') {
			$(this).parent().siblings('p').show();
			$(this).text('Less');
		} else {
			$(this).parent().siblings('p').hide().first().show();
			$(this).show().text('Learn more');
		}
	});



// homepage


  // $('.area-link a').hide();


  $('.area-link img').hover(
    function() {
      $(this).stop().parent().siblings('.highlight').fadeIn('fast');
    },
    function() {
      $(this).stop().parent().siblings('.highlight').fadeOut('fast');
  });
  



  // $(".area-link-blurb").hover(
  // function() {
  // $(this).stop().animate({"opacity": "1"}, "slow");
  // },
  // function() {
  // $(this).stop().animate({"opacity": "0"}, "normal");
  // });



  // $('.area-link-blurb').hover(
  //   function() {
  //     $(this).css({ opacity: 0.9})
  //   },
  //   function() {
  //     $(this).stop().animate({ opacity: 0}, "fast")
  // });


// nav bar

  $('.index_expand').hide()

  // $('#area_index-toggle').toggle(function() {
  //  $('.index_expand').show();
  //  $(this).css({borderBottom: 'none'});
  // }, function () {
  //   $('.index_expand').hide();
  //  $(this).css({borderBottom: 'solid 1px #719ac4'});
  // });
  //

  function expando(e) {
    e.preventDefault();
    e.stopPropagation();
    if ($('.index_expand').is(":visible")) {
      $('.index_expand').hide();
      $(this).css({borderBottom: 'solid 1px #719ac4'});
    } else {
      $('.index_expand').show();
      $(this).css({borderBottom: 'none'});
    }
  }

  $('#area_index-toggle').click(expando);


  $("body").click(function() {
    $(".index_expand").hide();
  });

  $(".index_expand").click(function(e) {
      e.stopPropagation();
  });


// internal nav scroll

  $('#internal-nav a').click(function() {
    $('#scroll-window').scrollTo((this.hash), {duration: 750});
    return false;
  });


// LOGO SELECTOR

  $('.logo-selector-options').hide().first().show();

 // $('#panel-4 .logo-selector-options:nth-child(1)').show();

// panel-1 a clicks
  $('#panel-1 a').click(function() {
    $('#panel-2 > div, #panel-3 > div, #panel-4 > div').hide();
    $('#panel-2 a, #panel-3 a, #panel-4 a').removeClass('selected')
    $('#panel-2 .logo-selector-options').filter(this.hash).show();
    $('#panel-3, #panel-4').parent().removeClass('active');
    $('#panel-2').parent().addClass('active');
    // removing headers from panel-4
    $('#panel-4-heading').html('');
    // remove subtext from panels
    $('#panel-3-subtext, #panel-4-subtext').html('');
    // set panel 2 subtext
    if ($(this).text() == 'Melbourne University') {
			$('#panel-2-subtext').html('Please select<br/>the logo usage');
		}
		else if ($(this).text() == 'Faculty Logos') {
			$('#panel-2-subtext').html('Please select<br/>a Faculty');
		}
		else {
		  $('#panel-2-subtext').html('Please select<br/>a Graduate school');
		  }
    return false
  });

// panel-2 a clicks
  $('#panel-2 a').click(function() {
    $('#panel-3 > div, #panel-4 > div').hide();
    $('#panel-3 a, #panel-4 a').removeClass('selected')
    $('#panel-3 .logo-selector-options').filter(this.hash).show();
    $('#panel-4').parent().removeClass('active');
    $('#panel-3').parent().addClass('active');
    // removing headers from panel-4
    $('#panel-4-heading').html('');
    // remove subtext from panels
    $('#panel-4-subtext').html('');
    // set panel 3 subtext
    $('#panel-3-subtext').html('Please select<br/>a logo style');
    return false
  });

// panel-3 a clicks
  $('#panel-3 a').click(function() {
    $('#panel-4 > div').hide();
    $('#panel-4 a').removeClass('selected')
    $('#panel-4 .logo-selector-options').filter(this.hash).show();
    $('#panel-4').parent().addClass('active');
    // adding headers to panel-4
    $('#panel-4-heading').html($(this).html());
    // set panel 4 subtext
    if ($(this).text() == 'Primary' || $('#research-institutes-selector').hasClass('selected')) {
			$('#panel-4-subtext').html('The primary logo on blue background is to be<br/>used on <em>all</em> materials where possible');
		}
		else if ($(this).text() == 'Secondary') {
			$('#panel-4-subtext').html('The secondary logo is to be used in instances where the<br/>blue background cannot be used for the material');
    }
    else if ($('#public-lecture-selector').hasClass('selected')) {
     $('#panel-4-subtext').html('The Public Lecture logos come in either singular - "PublicLecture"<br/> or plural - "PublicLectures"');
		}
		else {
		  $('#panel-4-subtext').html('Lineart and B&W logos are to be used in<br/>instances of colour printing restraints');
		  }
    return false
  });

// adding selected class
  $('#panel-1 a, #panel-2 a, #panel-3 a').click(function() {
    $(this).parent().siblings().contents().removeClass('selected');
    $(this).addClass('selected');
  });

// MERCHANDISE

  $('.expand').hide();
  $('.close-button').hide();

  // HEADING EXPAND TOGGLE

  $('.merchandise-item-group h3 a').click(function() {
    $('.close-button').fadeOut('fast');
    
      if ($(this).parent().siblings('.expand').is(':visible')) {
        $(this).parent().siblings('.expand').slideUp();
        $('.expand-link').fadeIn('fast');
        return false;
      }
      else {
        $('.expand').slideUp();
        $(this).parent().siblings('.expand').slideDown();
        $(this).parent().siblings('.expand-link').fadeOut('fast');
        $(this).parent().siblings('.close-button').fadeIn('fast');
        return false;
      }
    });


  // FIND OUT MORE FUNCTION
  
  $('.expand-link').click(function() {
    $('.expand').slideUp();
    $('.close-button').fadeOut('fast');
    $('.expand-link').fadeIn('fast');
    $(this).fadeOut('fast');
    $(this).siblings('.expand').slideDown();
    $(this).siblings('.close-button').fadeIn('fast');
    return false;
  });

  // CLOSE BUTTON FUNCTION

  $('.close-button').click(function() {
    $(this).fadeOut('fast');
    $(this).siblings('.expand').slideUp();
    $(this).siblings('.expand-link').fadeIn('fast');
    return false;
  });

  // CLOSE LINK FUNCTION

  $('.close-link').click(function() {
    $(this).parents('.expand').slideUp();
    $(this).parent().siblings('.expand-link').fadeIn('fast')
    $(this).parent().siblings('.close-button').fadeOut('fast');
    return false;
  });

  // ITEM INFORMATION ROLLOVERS
  
  $('.item-information').hover(
      function() {
          $(this).stop().animate({ opacity: 0.9})
        },
        function() {
          $(this).stop().animate({ opacity: 0}, "fast")
      });

  $('#form-use').waypoint(function(event, direction) {
      $(this).toggleClass('sticky', direction === "down");
      // $(this).next().toggleClass('scroll-smoother', direction === "down");
      event.stopPropagation();
    }, {offset: 10});
  
  $('#form-use a').click(function(e) {
  
    $('#signage-form select, #signage-form input').each(function() {
      var v = $(this).val();
      var n = $(this).attr("name");
      $('#product-form input[name*="'+n+'"]').remove();
      if (v != 0) {
        var html = '<input type="hidden" name="'+n+'" value="'+v+'"/>';        
        $("#product-form").append(html);
      }
    })
    $('#product-form').submit();
    return false;
  });

	//ACCORDION BUTTON ACTION (ON CLICK DO THE FOLLOWING)
	$('.linkmenu-title').click(function() {

		//REMOVE THE ON CLASS FROM ALL BUTTONS
		$('.linkmenu-title').removeClass('on');

		//NO MATTER WHAT WE CLOSE ALL OPEN SLIDES
	 	$('.linkmenu-body').slideUp('normal');

		//IF THE NEXT SLIDE WASN'T OPEN THEN OPEN IT
		if($(this).next().is(':hidden') == true) {

			//ADD THE ON CLASS TO THE BUTTON
			$(this).addClass('on');

			//OPEN THE SLIDE
			$(this).next().slideDown('normal');
		 } 

	 });

	/********************************************************************************************************************
	CLOSES ALL S ON PAGE LOAD
	********************************************************************************************************************/	
	$('.linkmenu-body').hide();

});