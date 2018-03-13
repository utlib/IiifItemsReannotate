# IIIF Toolkit Annotation Remapper

This plugin enables annotations made by IIIF Toolkit to be mapped between corresponding manifests, scaled according to a shared reference area. You can use this to start annotating with a lower-quality image first, then adapt your work to a higher resolution canonical image later on.

## System Requirements

* Omeka Classic 2.4 and up
* PHP 5.5+
* MySQL 5.5+
* IIIF image server pointing to the Omeka installation's files/original directory (optional if you will only be importing content from existing manifests)
* IIIF Toolkit 1.0.1 and up

## Installation

* Clone this repository to the `plugins` directory of your Omeka installation.
* Sign in as a super user.
* In the top menu bar, select "Plugins".
* Find "IIIF Toolkit Annotation Remapper" in the list of plugins and select "Install".

## How to Use

* From the admin-side menu, click "Annotation Remapper".
* Click "Start New Task".
* Name your new task and select the image source and target. Proceed by clicking "Create Task".
* Create pairings between source and target images using the mini IIIF viewers. Use the arrow buttons to switch between images.
    * If you do not want annotations from the current source image to be remapped, click "Pass". This will move onto the next image.
    * If you wish to carry over annotations from the current source image to the target image, adjust the reference area selector on both the source and target viewers by dragging the borders and corner knobs, then click "Confirm". Generally, larger reference regions will give more accurate mappings. Examples include picture frames, page borders or the space between two contrasting objects.
    * If you wish to undo a mapping, use the < and > arrow buttons to navigate to the source image to undo, then click "Reset".
* Once you have paired and/or passed all the source and target images you wish to map, click "Run Job" to start mapping the annotations.

## License

We would like to extend our thanks to the authors of this plugin's dependencies:

- [OpenSeadragon](https://openseadragon.github.io) by CodePlex Foundation, licensed under BSD 3
- [IIIF Cropper](https://github.com/sul-dlss/iiif-cropper) by Stanford University Digital Library

IIIF Toolkit Annotation Remapper is licensed under Apache License 2.0.
