=== Responsive Images ===
Contributors: richard.venancio
Donate link: https://packsystem.com.br
Tags: Images, Images Art Direction, Responsive Images, Responsive, Customize Images
Requires at least: 5.2
Tested up to: 5.2
Stable tag: 1.1.3

This plugin is to edit the images that WordPress create automatically.

== Description ==

With the request that search engines ask around images, is difficulty to manager the right sizes and have a good crop for each image size that we have on our theme. 

With that in mind, we create an option to replace just one size of the image, then when the image shows up on mobile or tablet, it will show de crop that you uploaded instead the one that Wordpress made.

= Performance (Devs) =

We add filters and actions mainly on backend. Any updates on that will be here.

Filters on Front end
* wp_calculate_image_srcset
* wp_calculate_image_sizes

To turn off the filter, just add a filter ('CRI_change_sizes_attr' and 'CRI_change_srcset_attr') returning false.

= Recommended Plugins =

* [PNG to JPG](https://wordpress.org/plugins/png-to-jpg/) by KubiQ - Use this first to convert all the PNG's to JPG's
* [Regenerate Thumbnails](https://wordpress.org/plugins/regenerate-thumbnails/) by Alex Mills - IF you change the images sizes or add new ones, use this to regenerate all the images.

== Installation ==
1. Upload the entire `change-responsive-images-rv` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Media Description, and set the name and descriptions for all images that WordPress create.
4. Go to media library click the image that you want to change and click Edit more details.
5. Use the function get_attachment_picture.

== Changelog ==

= 1.1.0 =

* Fix Readme.txt
* Option to set which images that will be used for responsive purpose.
* Option to set which images can be replaced.
* Option to set the default image attribute sizes and another for loops.

= 1.1.1 =

* Small Fix for calculate images sizes in loops.

= 1.1.2 =

* Fix error on save images.

= 1.1.3 =

* Fix max image width for 'srcset'
* Change the name Media Descriptions to Media Options.
* Create function get_attachment_picture. It works exactly as wp_get_attachment_image but instead return an image tag with srcset and sizes, it returns a picture tag, with the sources.

= Futures =

* Create a better layout for Media Descriptions.
* Add an option at the panel to disable the filter to 'srcset' and 'sizes' attributes.
* Add a function to check if all the files in images metadata are available.
