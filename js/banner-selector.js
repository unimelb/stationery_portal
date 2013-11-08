$(document).ready(function() {

	// BANNER SELECTOR

	  $('.banner-selector-options').hide().first().show();

	 // $('#panel-4 .banner-selector-options:nth-child(1)').show();

	// panel-1 a clicks
	  $('#panel-1 a').click(function() {
	    $('#panel-2 > div, #panel-3 > div, #panel-4 > div').hide();
	    $('#panel-2 a, #panel-3 a, #panel-4 a').removeClass('selected')
	    $('#panel-2 .banner-selector-options').filter(this.hash).show();
	    $('#panel-3, #panel-4').parent().removeClass('active');
	    $('#panel-2').parent().addClass('active');
	    // removing headers from panel-4
	    $('#panel-4-heading').html('');
	    // remove subtext from panels
	    $('#panel-3-subtext, #panel-4-subtext').html('');
	    // set panel 2 subtext
	    if ($(this).text() == 'Change of Preference') {
				$('#panel-2-subtext').html('Please select<br/>the logo usage');
			}
	    return false
	  });

	// panel-2 a clicks
	  $('#panel-2 a').click(function() {
	    $('#panel-3 > div, #panel-4 > div').hide();
	    $('#panel-3 a, #panel-4 a').removeClass('selected')
	    $('#panel-3 .banner-selector-options').filter(this.hash).show();
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
	    $('#panel-4 .banner-selector-options').filter(this.hash).show();
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


});