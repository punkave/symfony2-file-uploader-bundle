PunkAveFileUploaderBundle
=========================

# Maintainer Needed!

This bundle is no longer actively updated. It is maintained for bugs affecting use with Symfony 2.0.x only. If you are interested in taking over maintenance of this bundle, please contact [tom@punkave.com](mailto:tom@punkave.com). Thanks!

Introduction
============

This bundle provides multiple file uploads, based on the [BlueImp jQuery file uploader](https://github.com/blueimp/jQuery-File-Upload/) package. Both drag and drop and multiple file selection are fully supported in compatible browsers. We chose BlueImp because it has excellent backwards and forwards browser compatibility.

This bundle is a fairly thin wrapper because the existing PHP uploader class provided by BlueImp is very good already and does so many excellent things straight out of the box. We provided a way to integrate it into a Symfony 2 project.

The uploader delivers files to a folder that you specify. If that folder already contains files, they are displayed side by side with new files, as existing files that can be removed.

The bundle can automatically scale images to sizes you specify. The provided synchronization methods make it possible to create forms in which attached files respect "save" and "cancel" operations.

Note on Internet Explorer
=========================

Versions of Internet Explorer prior to 10 have no support for multiple file uploads. However IE users will be able to add a single file at a time and will still be able to build a collection of attached files.

Requirements
============

* Symfony2
* jQuery
* jQuery UI
* Underscore

Installation
============

Symfony 2.0
-----------
1) Add the following line to your Symfony2 deps file:

    [FileUploaderBundle]
        git=http://github.com/punkave/symfony2-file-uploader-bundle.git
        target=/bundles/PunkAve/FileUploaderBundle

2) Modify your AppKernel with the following line:

    new PunkAve\FileUploaderBundle\PunkAveFileUploaderBundle(),

3) If you do not already have it, add the following line to your autoload.php file:

    'PunkAve' => __DIR__.'/../vendor/bundles',

4) Install your vendors:

    bin/vendors install

Symfony 2.2
-----------
1) Add the following line to your composer.json require block:
    "punkave/symfony2-file-uploader-bundle": "dev-master"

    The standard symfony 2.2 composer.json file has a branch alias that interferes with installing this bundle.  You can work around by removing the lines
```
 "branch-alias": {
            "dev-master": "2.2-dev"
        }
```

2) Modify your AppKernel with the following line:

    new PunkAve\FileUploaderBundle\PunkAveFileUploaderBundle(),

3) Execute composer install

Usage
=====

Your page must contain Underscore templates to render the file list and uploader. You can use our templates like this:

    {# Underscore templates for the uploader #}
    {% include "PunkAveFileUploaderBundle:Default:templates.html.twig" %}

It is sufficient to do so anywhere in the body. You can copy and modify templates.html.twig if you wish and include it from your own directory. Just don't remove the data-* attributes. The rest of the markup is up to you.

In the Edit Action
==================

Let's assume you have an editAction() method in a controller. You have a form in which you would like to include a list of attached files that work like other fields in the form: you can add more, you can remove existing files, but nothing permanent happens unless the user clicks "save."

The FileUploader service needs a unique folder name for the files attached to a given object. To accomplish this for new objects as well as existing objects, we suggest you follow the "editId pattern," in which a form is assigned a unique, random "editId" for its entire lifetime, including multiple passes of validation if necessary. This allows us to manage file uploads for new objects that don't have their own id yet.

This code takes creat of creating an editId on the first pass through the form and syncs existing files attached to an existing object, if any. The from_folder and to_folder objects specify subdirectories where the attached files will be stored. Later we'll look at how the parent directories of these folders are determined.

(Fetching $posting and validating that the user is allowed to edit that posting is up to you.)

    $request = $this->getRequest();

    $editId = $this->getRequest()->get('editId');
    if (!preg_match('/^\d+$/', $editId))
    {
        $editId = sprintf('%09d', mt_rand(0, 1999999999));
        if ($posting->getId())
        {
            $this->get('punk_ave.file_uploader')->syncFiles(
                array('from_folder' => 'attachments/' . $posting->getId(),
                  'to_folder' => 'tmp/attachments/' . $editId,
                  'create_to_folder' => true));
        }
    }

If the user encounters a validation error on their first attempt to complete the action (for instance, a form validation error), you'll want to present the same list of files again. So use the `getFiles` method to obtain a list of existing files. Make sure you pass that list to your template.

    $existingFiles = $this->get('punk_ave.file_uploader')->getFiles(array('folder' => 'tmp/attachments/' . $editId));

(Note that the editId you generate should be highly random to prevent users from gaining control of each other's attachments.)

When the user saves the form and you have just persisted the posting object, you should also sync files back from the temporary folder associated with the editId to the permanent one associated with the posting's id. Since we are done with the temporary folder we ask the file uploader service to remove that folder. We also ask the service to create the destination folder if necessary:

    $fileUploader = $this->get('punk_ave.file_uploader');
    $fileUploader->syncFiles(
        array('from_folder' => '/tmp/attachments/' . $editId,
        'to_folder' => '/attachments/' . $posting->getId(),
        'remove_from_folder' => true,
        'create_to_folder' => true));

Later you can easily obtain a list of the names of all files attached to an object:

    $files = $fileUploader->getFiles(array('folder' => 'attachments/' . $posting->getId()));

However there is a performance cost associated with accessing the filesystem. You will find it more efficient to keep a list of attachments in a Doctrine table, especially if you want to include the first attachment in a list view. Just use getFiles to get the list of filenames and mirror that in your database as you see fit.

In Your Layout
==============

To make the necessary JavaScript available via Assetic (note that you must supply jQuery, jQuery UI and Underscore):

    {% javascripts
        '@MyBundle/Resources/public/js/jquery-1.7.2.min.js'
        '@MyBundle/Resources/public/js/jquery-ui-1.8.22.custom.min.js'
        '@MyBundle/Resources/public/js/underscore-min.js'
        '@PunkAveFileUploaderBundle/Resources/public/js/jquery.fileupload.js'
        '@PunkAveFileUploaderBundle/Resources/public/js/jquery.iframe-transport.js'
        '@PunkAveFileUploaderBundle/Resources/public/js/FileUploader.js' %}
        <script src="{{ asset_url }}"></script>
    {% endjavascripts %}

You must include the iframe transport for compatibility with IE 9 and below.

In the Edit Template
====================

Let's assume there is an edit.html.twig template associated with the edit action. Here's what it might look like. Note that the render call in your action would pass in the posting object, the editId, the existingFiles array and the isNew flag:

    {% extends "MyBundle:Default:layout.html.twig" %}

    {% block body %}

    {# Underscore templates for the uploader #}
    {% include "PunkAveFileUploaderBundle:Default:templates.html.twig" %}

    <form class="edit-form" action="{{ path('edit', { id: posting.id, editId: editId }) }}" method="post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}

        {# Hydrated by javascript #}
        <div class="file-uploader"></div>

        <button class="btn btn-primary" type="submit">{{ isNew ? "Save New Listing" : "Save Changes" }}</button>
        <a class="btn" href="{{ cancel }}">Cancel</a>
        {% if not isNew %}
            <a class="btn btn-danger" href="{{ path('delete', { id: posting.id } ) }}">Delete</a>
        {% endif %}

    </form>

    <script type="text/javascript">

    // Enable the file uploader

    $(function() {
        new PunkAveFileUploader({
            'uploadUrl': {{ path('upload', { editId: editId }) | json_encode | raw }},
            'viewUrl': {{ ('/uploads/tmp/attachments/' ~ editId) | json_encode | raw }},
            'el': '.file-uploader',
            'existingFiles': {{ punkave_get_files('tmp/attachments/' ~ editId) | json_encode | raw }},
            'delaySubmitWhileUploading': '.edit-form'
        });
    });
    </script>
    {% endblock %}

Progress Display
================

There is a simple spinner in template.html.twig. If you choose to provide your own Underscore templates you can replace it. Just make sure you have your own element with a data-spinner="1" attribute.

If you are using template.html.twig, note that you must publish your assets in the usual way for the spinner image to be available:

    php app/console assets:install web/

As an alternative, you can write your own code on an interval timer that checks whether the `uploading` property of the PunkAveFileUploader object is currently set to true and display a spinner on that basis.

Delaying Form Submission Until Uploads Complete
===============================================

It's not a good idea to let the user submit a form that the file uploader is meant to be part of if uploads are still in progress. You can easily block this by specifying the 'delaySubmitWhileUploading' option as shown above when creating the PunkAveFileUploader JavaScript object:

    'delaySubmitWhileUploading': '.edit-form'

Alternatively, you can check the `uploading` property of the object you create with `new PunkAveFileUploader(...)` at any time. It will be true if an upload is in progress. The existing implementation of `delaySubmitWhileUploading` relies on this.

In the Upload Action
====================

In addition to the regular edit action of your form, there must be an upload action to handle file uploads. This action will call the handleFileUpload method of the service to pass on the job to BlueImp's UploadHandler class. Since that class implements the entire REST response directly in PHP, the method does not return.

Here is the upload action:

    /**
     *
     * @Route("/upload", name="upload")
     * @Template()
     */
    public function uploadAction()
    {
        $editId = $this->getRequest()->get('editId');
        if (!preg_match('/^\d+$/', $editId))
        {
            throw new Exception("Bad edit id");
        }

        $this->get('punk_ave.file_uploader')->handleFileUpload(array('folder' => 'tmp/attachments/' . $editId));
    }

This single action actually implements a full REST API in which the BlueImp UploadHandler class takes care of uploading as well as deleting files.

Again, handleFileUpload DOES NOT RETURN as the response is generated in native PHP by BlueImp's UploadHandler class.

Setting the allowed file types
------------------------------
You can specify custom file types to divert from the default ones (which are defined in Resources/config/services.yml) by either specifing
them in the handleFileUpload method or parameters.yml.

***In the handleFileUpload:***

    $this->get('punk_ave.file_uploader')->handleFileUpload(array(
        'folder' => 'tmp/attachments/' . $editId,
        'allowed_extensions' => array('zip', 'rar', 'tar')
    ));

In this case the FileUploader service will merge the default extensions with the supplied extensions and make a single regex of it. Using regular expression characters could result in errors.

***Parameters.yml:***
If you have the Symfony standard edition installed you can specify them in app/config/parameters.yml:

    file_uploader.allowed_extensions:
        - zip
        - rar
        - tar

Doing this will override the default extensions instead of adding them!

Removing Files
==============

Sooner or later the posting is deleted and you want all of the attachments to be deleted as well.

You can do this as follows:

    $this->get('punk_ave.file_uploader')->removeFiles(array('folder' => 'attachments/' . $posting->getId()));

You might want to do that in a manager class or a doctrine event listener, but that's not our department.

Removing Temporary Files
========================

If you choose to follow our editId pattern, you'll want to purge contents of web/uploads/tmp that are over a certain age on a periodic basis. People walk away from websites a lot, so not everyone will click your thoughtfully provided "cancel" action that calls removeFiles() based on the editId pattern.

Consider installing this shell script as a cron job to be run nightly. This shell script deletes files more than one day old, then deletes empty folders:

    #!/bin/sh
    find /path/to/my/project/web/uploads/tmp -mtime +1 -type f -delete
    find /path/to/my/project/web/uploads/tmp -mindepth 1 -type d -empty -delete

(Since the second command is not recursive, the parent folders may stick around an extra day, but they are removed the next day.)

Configuration Parameters
========================

See `Resources/config/services.yml` in this bundle. You can easily decide what the parent folder of uploads will be and what file extensions are accepted, as well as what sizes you'd like image files to be automatically scaled to.

The `from_folder`, `to_folder`, and `folder` options seen above are all appended after `file_uploader.file_base_path` when dealing with files.

If `file_uploader.file_base_path` is set as follows (the default):

    file_uploader.file_base_path: "%kernel.root_dir%/../web/uploads"

And the `folder` option is set to `attachments/5` when calling `handleFileUpload`, then the uploaded files will arrive in:

    /root/of/your/project/web/uploads/attachments/5/originals

If the only attached file for this posting is `botfly.jpg` and you have configured one or more image sizes for the `file_uploader.sizes` option (by default we provide several useful standard sizes), then you will see:

    /root/of/your/project/web/uploads/photos/5/originals/botfly.jpg
    /root/of/your/project/web/uploads/photos/5/thumbnail/botfly.jpg
    /root/of/your/project/web/uploads/photos/5/medium/botfly.jpg
    /root/of/your/project/web/uploads/photos/5/large/botfly.jpg

So all of these can be readily accessed via the following URLs:

    /uploads/photos/5/originals/botfly.jpg

And so on.

The original names and file extensions of the files uploaded are preserved as much as possible without introducing security risks.

Limit number of uploads
-----------------------

You can limit the number of uploaded files by setting the `max_no_of_files` property. You could set this in parameters.yml like this:

    parameters:
      file_uploader.max_number_of_files: 4

You'll probably want to add an error handler for this case. In the template where you initialize PunkAveFileUploader set `errorCallback`

    // Enable the file uploader
    $(function() {
      new PunkAveFileUploader({
        // ... other required options,

        'errorCallback': function(errorObj) {
          if (errorObj.error == 'maxNumberOfFiles') {
            alert("Maximum uploaded files exceeded!");
          }
        }
      });
    });

Limitations
===========

This bundle accesses the file system via the `glob()` function. It won't work out of the box with an S3 stream wrapper.

Syncing files back and forth to follow the editId pattern might not be agreeable if your attachments are very large. In that case, don't use the editId pattern. One alternative is to create objects immediately in the database and not show them in the list view until you mark them live. This way your edit action can use the permanent id of the object as part of the `folder` option, and nothing has to be synced. In this scenario you should probably move the attachments list below the form to hint to the user that there is no such thing as "cancelling" those actions.

Notes
=====

The uploader has been styled using Bootstrap conventions. If you have Bootstrap in your project, the uploader should look reasonably pretty out of the box.

The "Choose Files" button allows multiple select as well as drag and drop.
