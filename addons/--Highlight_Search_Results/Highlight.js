$(function(){

  // highlight search queries

  var queries = {};
  $.each(document.location.search.substr(1).split('&'), function(c, q){
    var i = q.split('=');
    queries[i[0].toString()] = i[1].toString();
  });

  if( !'highlight' in queries ){
    return;
  }

  $('#gpx_content')
    .highlight(decodeURIComponent(queries.highlight), {
      element         : 'span',               // default 'span'
      className       : 'text-highlighted',   // default 'highlight'
      caseSensitive   : false,                // default false
      wordsOnly       : false                 // default false
    });

});
