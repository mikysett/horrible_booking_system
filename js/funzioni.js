/* @Note: not sure e.pageX will work in IE8 -- http://www.rickyh.co.uk/css-position-x-and-position-y/ */
(function(window){
  
  /* A full compatability script from MDN: */
  var supportPageOffset = window.pageXOffset !== undefined;
  var isCSS1Compat = ((document.compatMode || "") === "CSS1Compat");
 
  /* Set up some variables  */
  var demoItem2 = document.getElementById("fixed_calendar");
  var demoItem3 = document.getElementById("fixed_camere"); 
  /* Add an event to the window.onscroll event */
  window.addEventListener("scroll", function(e) {  
    
    /* A full compatability script from MDN for gathering the x and y values of scroll: */
    var x = supportPageOffset ? window.pageXOffset : isCSS1Compat ? document.documentElement.scrollLeft : document.body.scrollLeft;
var y = supportPageOffset ? window.pageYOffset : isCSS1Compat ? document.documentElement.scrollTop : document.body.scrollTop;
 
    demoItem2.style.left = -x + 0 + "px";
    demoItem3.style.top = -y + 112 + "px";
  });
  
})(window);