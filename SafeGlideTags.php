<?php

namespace Statamic\Addons\SafeGlide;

use Statamic\Addons\Glide\GlideTags;
use Statamic\Extend\Tags;
use Statamic\API\Asset;
use Statamic\API\Config;

class SafeGlideTags extends GlideTags
{
    public function __call( $method, $args )
    {

      //get this item via either id or url
      $tag = explode( ':', $this->tag, 2 )[1];
      $item = array_get( $this->context, $tag );

      //get the image URL of the image, even if SafeGlide:id and not SafeGlide:url was used
      $imageURL = array_get( $this->context, 'image' );

      // get addon preferences
      $maxW = $this->getConfigInt( 'max_width', 0 ) ;
      $maxH = $this->getConfigInt( 'max_height', 0 );
      $use_log = $this->getConfigBool( 'use_log', false );
      $fallback = $this->getConfigBool( 'fallback', true );

      // only carry out checks if the maximum width and height are set
      if( $maxW > 0 && $maxH > 0 ) {

        // get the dimensions of this asset
        $asset = Asset::find( $item );
        $w = $asset->width();
        $h = $asset->height();

        // if it's too large...
        if ( $w > $maxW || $h > $maxH ) {

          // log (if required)
          if( $use_log ) {
            \Log::info(
              "Asset `$item` ($w x $h) is too large to resize. ".
              "Maximum size is $maxW x $maxH. ".
              ( $fallback
                ? 'Falling back to original image.'
                : 'The image was not served.'
              )
            );
          }

          // fall back to the original image (if required)
          if( $fallback ) {
            return $item; // the URL of the original image
          } else {
            return "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"; // 1x1 transparent GIF
          }

        }

      } else {

        \Log::info(
          "SafeGlide has done nothing as the maximum width and height are not set. Update these in Settings > Addons > SafeGlide."
        );

      }

      //pass everything to Glide
      return parent::__call( $method, $args );

    }
}
