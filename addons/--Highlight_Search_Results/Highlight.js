$(function(){

  // find search queries
  var search = document.location.search.substr(1);
  search = search.length ? search.split('&') : false;
  // console.log("search = ", search);
  if( !search ){
    return;
  }

  var queries = {};
  $.each(search, function(c, q){
    var i = q.split('=');
    queries[i[0].toString()] = i[1].toString();
  });

  // console.log("queries = ", queries);
  if( !'highlight' in queries ){
    return;
  }

  // highlight
  $('#gpx_content')
    .highlight(decodeURIComponent(queries.highlight), {
      element         : 'span',               // default = 'span'
      className       : 'text-highlighted',   // default = 'text-highlighted'
      caseSensitive   : false,                // default = false
      wordsOnly       : false                 // default = false
    });

  // unhighlight for editing
  if( gpadmin ){
    $(document).on('editor_area:loaded', function(){
      $('#gpx_content').unhighlight();
    });
  }

});
