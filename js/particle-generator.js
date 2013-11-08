/*
 * jQuery throttle / debounce - v1.1 - 3/7/2010
 * http://benalman.com/projects/jquery-throttle-debounce-plugin/
 * 
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */
(function(b,c){var $=b.jQuery||b.Cowboy||(b.Cowboy={}),a;$.throttle=a=function(e,f,j,i){var h,d=0;if(typeof f!=="boolean"){i=j;j=f;f=c}function g(){var o=this,m=+new Date()-d,n=arguments;function l(){d=+new Date();j.apply(o,n)}function k(){h=c}if(i&&!h){l()}h&&clearTimeout(h);if(i===c&&m>e){l()}else{if(f!==true){h=setTimeout(i?k:l,i===c?e-m:e)}}}if($.guid){g.guid=j.guid=j.guid||$.guid++}return g};$.debounce=function(d,e,f){return f===c?a(d,e,false):a(d,f,e!==false)}})(this);

(function() {

  var helpers = {
    createElement: function(tag, attr) {
      var key, elem = document.createElement(tag);
      for (key in attr) {
        if (key === 'className') elem['class'] = attr[key];
        elem[key] = attr[key];
      }
      return elem;
    }
  }

  var controller = {
    canvas : null,
    ctx : null,
    blendMode: 1,
    blendModes: [ "source-over", "lighter", "darker", "xor" ],

    init : function(){
      this.canvas = document.getElementById("particles");
      this.ctx = this.canvas.getContext("2d");

      this.setBlendMode(this.blendMode);
      this.pe = new cParticleEmitter(this.ctx);
      this.pe.init();
      // Run!
      this.main();
    },
    main: function(){
      for (var i = 0, ii = 4; i < ii; i++) {
        this.update();
        this.draw();
      };
    },
    update: function(){
      this.pe.update(1);
    },
    draw: function(){
      this.pe.renderParticles(this.ctx);
    },
    setBlendMode: function(mode){
      this.blendMode = mode;
      this.ctx.globalCompositeOperation = this.blendModes[ mode >= 0 && mode < this.blendModes.length ? mode : 0 ];
    }
  }


/*

        Inject the Canvas tag

                                    */
  var canvas = helpers.createElement('canvas', {
    id: 'particles', width: 2100, height: 500
  });

  // If canvas isn't supported, don't do anything else
  if (!(canvas.getContext && canvas.getContext('2d'))) return;
  // Inject the canvas tag and its wrapper
  var wrapper = helpers.createElement('div', { id: 'particle-wrapper' });
  wrapper.appendChild(canvas);
  document.body.insertBefore(wrapper, document.body.firstChild);

/*

        Initialise the particle generator

                                              */
  controller.init.apply(controller);

/*

        jQuery hover/resize events

                                              */

  $(function() {
    var timer,
        canvas = controller.ctx.canvas;
    // On animate, clear the canvas and play the next particle frame
    var animate = function() {
      controller.ctx.clearRect(0, 0, canvas.offsetWidth, canvas.offsetHeight);
      controller.main();
    }

    // On window resize, if the window is bigger than the canvas, resize 
    // the canvas to fit the width and regenerate the particles
    $(window).resize( $.throttle(25, function() {
      var w = $(this),
          canvas = $(controller.canvas);
      if (w.width() > 2100) {
        canvas.width(w.width()).css('margin-left', -w.width() / 2);
        controller.pe.resetWidth();
      }
    }) ).resize();

    var transitionCanvas = function() {
      var snapshot = controller.ctx.getImageData(0, 0, canvas.offsetWidth, canvas.offsetHeight),
          parent = $(controller.canvas.parentNode).prepend($(controller.canvas).clone()),
          newCanvas = parent.children().first(),
          newCtx = newCanvas[0].getContext('2d');
      newCtx.putImageData(snapshot, 0, 0);
      newCanvas.css({
        zIndex: 2,
        opacity: 0,
        background: 'rgba(0, 51, 102, 1)'
      });

      newCanvas.animate({ opacity: 1 }, 2000);
      setTimeout(function() {
        animate();
        newCanvas.fadeOut(2000, function() {
          $(this).remove();
          setTimeout(transitionCanvas, 1000);
        });
      }, 2000);
    }
    $(controller.canvas).css('z-index', 1);
    setTimeout(transitionCanvas, 1000);

  });

})();