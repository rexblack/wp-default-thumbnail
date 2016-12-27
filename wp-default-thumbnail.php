<?php

/**
 Plugin Name: Default Thumbnail
 Plugin URI: http://github.com/benignware-labs/wp-default-thumbnail
 Description: Shows a placeholder thumbnail
 Version: 0.0.1
 Author: Rafael Nowrotek, Benignware
 Author URI: http://benignware.com
 License: MIT
*/

/**
 * Show a placeholder image with empty thumbnails
 */



function default_post_thumbnail_html( $html, $post_id = null, $post_thumbnail_id = null, $size = null, $attr = array() ) {
  global $_wp_additional_image_sizes;

  $attr = is_array($attr) ? $attr : array();
  $file = null;
  $width = 0;
  $height = 0;

  if ($size) {
    $sizes = get_intermediate_image_sizes();
    foreach ( $sizes as $_size ) {
      if ($_size === $size) {
        if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
          $width  = get_option( "{$_size}_size_w" );
          $height = get_option( "{$_size}_size_h" );
        } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
          $width = $_wp_additional_image_sizes[ $_size ]['width'];
          $height = $_wp_additional_image_sizes[ $_size ]['height'];
        }
      }
    }
  }

  if ($post_id && $size) {
    $thumb = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), $size );
    if ($thumb) {
      // Check if file exists
      $url = $thumb[0];
      $width = $thumb[1] ? $thumb[1] : $width;
      $height = $thumb[2] ? $thumb[2] : $height;
      $filename = parse_url($url, PHP_URL_PATH);
      if ($filename) {
        $file = $_SERVER['DOCUMENT_ROOT'] . "/" . $filename;
        if (file_exists($file)) {
          return $html;
        }
      }
    }
  }

  $attr = array_merge(array(
    'src' => get_template_directory_uri() . '/svg/logo.svg',
    //'src' => null,
    'class' => 'img-placeholder',
    'style' => array(
      'background' => '#efefef',
      'font-family' => 'Arial',
      'color' => '#cdcdcd',
      'font-size' => '22px'
    ),
    'width' => $width,
    'height' => $height
  ), $attr);

  $attr = apply_filters('default_thumbnail_atts', $attr);

  $style = $attr['style'];

  $svg_attr = array(
    'style' => $style,
    'width' => $width,
    'height' => $height
  );

  array_walk($svg_attr['style'], function(&$value, $key) { $value = "$key: $value"; });
  $svg_attr['style'] = implode("; ", $svg_attr['style']);

  $src = $attr['src'];

  if (!$src) {
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 $width $height'";
    foreach ($svg_attr as $key => $value) {
      $svg.= " $key='" . $value . "'";
    }
    $svg.= ">";
    $svg.= "<text x='" . $width/2 . "px' y='" . $height/2 . "px' text-anchor='middle' alignment-baseline='central'>$width x $height</text>";
    $svg.= "</svg>";
  }

  $extension = pathinfo($src, PATHINFO_EXTENSION);
  if ($extension === 'svg') {
    $filename = parse_url($src, PHP_URL_PATH);
    if ($filename) {
      $file = $_SERVER['DOCUMENT_ROOT'] . $filename;
      if (file_exists($file)) {
        $svg = file_get_contents($file);
        if ($svg) {
          $svg_dom = new DOMDocument();
          $svg_dom->loadXML($svg);
          foreach ($svg_attr as $key => $value) {
            $svg_dom->documentElement->setAttribute($key, $value);
          }
          $svg = $svg_dom->saveXML();
        }
      }
    }
  }

  if ($svg) {
    $attr['src'] = "data:image/svg+xml;utf8," . rawurlencode($svg);
  }

  $html = "<img";
  foreach ($attr as $key => $value) {
    if ($key == 'style') {
      array_walk($value, function(&$value, $key) { $value = "$key: $value"; });
      $value = implode("; ", array_values($value));
    }
    $html.= " $key=\"$value\"";
  }

  $html.= "/>";
  return $html;

  return '<img src="' . $placeholder_src . '"  class="' . $class_name . '" width="' . $width . 'px" height="' . $height . 'px"/>';
}
add_filter( 'post_thumbnail_html', 'default_post_thumbnail_html', 10, 5 );
?>
