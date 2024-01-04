<?php

namespace Drupal\ilr_registrations\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines IlrCommerceInfo class.
 */
class IlrCommerceInfo extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content() {
    return [
      '#type' => 'inline_template',
      '#template' => '<p>Please contact the <a href="mailto:{{webteam_email}}">ILR Webteam</a> for support.</p>',
      '#context' => [
        'webteam_email' => 'ilrweb@cornell.edu',
      ],
    ];
  }

}
