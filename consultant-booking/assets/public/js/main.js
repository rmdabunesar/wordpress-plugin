(function ($) {
  "use strict";

  var paged = 1;
  $("#load-more").on("click", function () {
    paged++;

    $.ajax({
      url: consultantBooking.ajax_url,
      method: "GET",
      data: {
        action: "cb_load_more",
        paged: paged,
      },
      success: function (response) {
        $(".list").append(response);
      },
      error: function (error) {
        console.log(error);
      },
    });
  });

  $("#consultant-search-form").on("submit", function (e) {
    e.preventDefault();

    var searchTerm = $("#consultant-search-input").val();

    $.ajax({
      url: consultantBooking.ajax_url,
      type: "POST",
      data: {
        action: "cb_consultant_search",
        s: searchTerm,
        nonce: $('input[name="nonce"]').val(),
      },
      beforeSend: function () {
        $("#consultant-search-results").html("<p>Searching...</p>");
      },
      success: function (response) {
        $("#consultant-search-results").html(response);
      },
      error: function () {
        $("#consultant-search-results").html("<p>Not found, try again.</p>");
      },
    });
  });

})(jQuery);
