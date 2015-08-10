# Simple Gallery

This is a very minimalistic and simple image gallery for your webspace. What makes it special?

- It's just one file (`index.php`). Drop it into a directory on your webspace, make sure the webserver has write-access to the directory and you're done.
- Images are converted to JPEGs by the browser before uploading them. Feel free to drop BMPs or whatever into the gallery.
- It should work on pretty much any PHP 5 webspace and doesn't need the GD library.
- No user accounts. You can upload or delete images with a special key in the URL.

If you want to share some images, have some webspace lying around and don't want to use a cloud thing or something complex: This is for you.

## Try it

You can look at a small example gallery here:

http://arkanis.de/projects/simple-gallery/

To see the upload and delete controls you can use the URL with the key for the example gallery:

http://arkanis.de/projects/simple-gallery/?key=NLRw{[b}8l8ZgQ1%29x_6SF-lir88tcimFP_

You can't delete or upload images there since I haven't given the webserver any rights to modify the gallery. Instead you'll just see error messages.

## Download

You can grab the script from here: http://arkanis.de/projects/simple-gallery/randomize-key.php

This will automatically generate a new random key for you. If you grab the script directly from github please change the secret key inside the script.

## Not perfect

The gallery isn't perfect but it serves my purposes for now. It's a pretty simple thing. The thumbnails it creates can
look jagged in some situations and when you upload large images the browser might hang a few seconds. If you have any
ideas to make it more perfect feel free to modify it. Push requests are welcome. :)