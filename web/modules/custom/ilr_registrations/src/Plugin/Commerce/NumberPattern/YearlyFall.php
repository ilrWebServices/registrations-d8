<?php

namespace Drupal\ilr_registrations\Plugin\Commerce\NumberPattern;

use Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\SequentialNumberPatternBase;
use Drupal\commerce_number_pattern\Sequence;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides a yearly number pattern that increments in the Fall.
 *
 * @CommerceNumberPattern(
 *   id = "yearly_fall",
 *   label = @Translation("Yearly Fall (Reset every year on Sep. 1, e.g. 2023-09-2 would return 2024)"),
 * )
 */
class YearlyFall extends SequentialNumberPatternBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'pattern' => '[pattern:year_P4M]-[pattern:number]',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function shouldReset(Sequence $current_sequence) {
    $generated_time = DrupalDateTime::createFromTimestamp($current_sequence->getGeneratedTime());
    $current_time = DrupalDateTime::createFromTimestamp($this->time->getCurrentTime());

    // Add 3 months to the times. This is confusing (to Jeff) but appears to
    // work.
    $interval = new \DateInterval('P4M');
    $generated_time->add($interval);
    $current_time->add($interval);

    return $generated_time->format('Y') != $current_time->format('Y');
  }

}
