jQuery(document).ready(function ($) {
  $("#iufe-import-form").on("submit", function (e) {
    e.preventDefault();
    var formData = new FormData();
    formData.append("file", $("#iufe_excel_file")[0].files[0]);
    formData.append("action", "iufe_upload_file");
    formData.append("nonce", iufe_ajax.nonce);

    // Reset progress bar
    $("#iufe-btn").attr("disabled", "disabled");
    $("#iufe-progress").css("width", "0%").text("0%");
    $("#iufe-status").html("");

    // Upload the file first
    $.ajax({
      url: iufe_ajax.ajax_url,
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      success: function (response) {
        if (response.success) {
          // Start processing rows in chunks after the file is uploaded
          processNextChunk(
            3,
            response.data.total_rows,
            response.data.total_rows
          ); // Start from row 3, chunk size 100
        } else {
          $("#iufe-status").html("<p>Error: " + response.data + "</p>");
        }
      },
    });
  });

  function processNextChunk(chunkStart, chunkSize, totalRows) {
    let intervalID = setInterval(()=>{
      getProgress();
    },10000);
    $.ajax({
      url: iufe_ajax.ajax_url,
      type: "POST",
      data: {
        action: "iufe_process_chunk",
        nonce: iufe_ajax.nonce,
        chunk_start: chunkStart,
        chunk_size: chunkSize,
      },
      success: function (response) {
        if (response.success) {
          var progress = response.data.progress;
          $("#iufe-progress")
            .css("width", progress + "%")
            .text(Math.round(progress) + "%");
          $("#iufe-status").html("<p>" + response.data.message + "</p>");

          if (progress < 100) {
            // Process the next chunk
            processNextChunk(
              response.data.next_chunk_start,
              chunkSize,
              totalRows
            );
          } else {
            clearInterval(intervalID);
            $("#iufe-status").html(
              "<p>All rows have been processed successfully!</p>"
            );
            $("#iufe-btn").removeAttr("disabled");
          }
        } else {
          $("#iufe-status").html("<p>Error: " + response.data + "</p>");
        }
      },
    });
  }
  function getProgress() {
    $.ajax({
      url: iufe_ajax.ajax_url,
      type: "POST",
      data: {
        action: "iufe_get_progress",
        nonce: iufe_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          var progress = response.data.progress;
          $("#iufe-progress")
            .css("width", progress + "%")
            .text(Math.round(progress) + "%");
        } else {
        }
      },
    });
  }
});


jQuery(document).ready(function ($) {
  // Handle the product import form submission
  $("#iufe-product-import-form").on("submit", function (e) {
    e.preventDefault();
    
    var formData = new FormData();
    formData.append("file", $("#iufe_product_excel_file")[0].files[0]);
    formData.append("action", "iufe_upload_product_file");
    formData.append("nonce", iufe_ajax.nonce);

    // Disable the button and reset the progress bar
    $("#iufe-product-btn").attr("disabled", "disabled");
    $("#iufe-product-progress").css("width", "0%").text("0%");
    $("#iufe-product-status").html("");

    // Upload the file first
    $.ajax({
      url: iufe_ajax.ajax_url,
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      success: function (response) {
        if (response.success) {
          // Start processing rows in chunks after the file is uploaded
          processNextProductChunk(2, 100, response.data.total_rows); // Start from row 2, chunk size 100
        } else {
          $("#iufe-product-status").html("<p>Error: " + response.data + "</p>");
          $("#iufe-product-btn").removeAttr("disabled");
        }
      },
      error: function () {
        $("#iufe-product-status").html("<p>Error occurred during file upload.</p>");
        $("#iufe-product-btn").removeAttr("disabled");
      },
    });
  });

  // Function to process each chunk of products
  function processNextProductChunk(chunkStart, chunkSize, totalRows) {
    $.ajax({
      url: iufe_ajax.ajax_url,
      type: "POST",
      data: {
        action: "iufe_process_product_chunk",
        nonce: iufe_ajax.nonce,
        chunk_start: chunkStart,
        chunk_size: chunkSize,
      },
      success: function (response) {
        if (response.success) {
          var progress = response.data.progress;
          
          // Update the progress bar and status message
          $("#iufe-product-progress")
            .css("width", progress + "%")
            .text(Math.round(progress) + "%");
          $("#iufe-product-status").html("<p>" + response.data.message + "</p>");

          // Continue processing the next chunk if not yet completed
          if (progress < 100) {
            processNextProductChunk(response.data.next_chunk_start, chunkSize, totalRows);
          } else {
            $("#iufe-product-status").html("<p>All products have been processed successfully!</p>");
            $("#iufe-product-btn").removeAttr("disabled");
          }
        } else {
          $("#iufe-product-status").html("<p>Error: " + response.data + "</p>");
          $("#iufe-product-btn").removeAttr("disabled");
        }
      },
      error: function () {
        $("#iufe-product-status").html("<p>Error occurred during product processing.</p>");
        $("#iufe-product-btn").removeAttr("disabled");
      },
    });
  }
});
