function PunkAveFileUploader(options)
{
  var self = this;
  var uploadUrl = options.uploadUrl;
  var viewUrl = options.viewUrl;
  var $el = $(options.el);
  uploaderTemplate = _.template($('#file-uploader-template').html());
  $el.html(uploaderTemplate({}));

  fileTemplate = _.template($('#file-uploader-file-template').html());
  editor = $el.find('[data-files="1"]');
  thumbnails = $el.find('[data-thumbnails="1"]');

  self.addExistingFiles = function(files)
  {
    _.each(files, function(file) {
      appendEditableImage({
        // cmsMediaUrl is a global variable set by the underscoreTemplates partial of MediaItems.html.twig
        'thumbnail_url': viewUrl + '/thumbnails/' + file,
        'url': uploadUrl + '/originals/' + file,
        'name': file
        });
    });
  };

  if (options.existingFiles)
  {
    self.addExistingFiles(options.existingFiles);
  }

  editor.fileupload({
    dataType: 'json',
    url: uploadUrl,
    done: function (e, data) {
      if (data)
      {
        _.each(data.result, function(item) {
          appendEditableImage(item);
        });
      }
    }
  });

  // Expects thumbnail_url, url, and name properties. thumbnail_url can be undefined if
  // url does not end in gif, jpg, jpeg or png. This is designed to work with the
  // result returned by the UploadHandler class on the PHP side
  function appendEditableImage(info)
  {
    var li = $(fileTemplate(info));
    li.find('[data-action="delete"]').click(function(event) {
      var file = $(this).closest('[data-name]');
      var name = file.attr('data-name');
      $.ajax({
        type: 'delete',
        url: setQueryParameter(uploadUrl, 'file', name),
        success: function() {
          file.remove();
        },
        dataType: 'json'
      });
      return false;
    });

    thumbnails.append(li);
  }

  function setQueryParameter(url, param, paramVal)
  {
    var newAdditionalURL = "";
    var tempArray = url.split("?");
    var baseURL = tempArray[0];
    var additionalURL = tempArray[1]; 
    var temp = "";
    if (additionalURL)
    {
        var tempArray = additionalURL.split("&");
        var i;
        for (i = 0; i < tempArray.length; i++)
        {
            if (tempArray[i].split('=')[0] != param )
            {
                newAdditionalURL += temp + tempArray[i];
                temp = "&";
            }
        }
    }
    var newTxt = temp + "" + param + "=" + encodeURIComponent(paramVal);
    var finalURL = baseURL + "?" + newAdditionalURL + newTxt;
    return finalURL;
  }
}


