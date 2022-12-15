<?php

namespace Drupal\ilr_registrations\Plugin\Commerce\NumberPattern;

use DateInterval;
use Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\SequentialNumberPatternBase;
use Drupal\commerce_number_pattern\Sequence;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides a yearly number pattern that increments in the Fall.
 *
 * @CommerceNumberPattern(
 *   id = "yearly_fall",
 *   label = @Translation("Yearly Fall (Reset every year on Oct. 1, e.g. 2022-11-2 would return 2023)"),
 * )
 */
class YearlyFall extends SequentialNumberPatternBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'pattern' => '[pattern:year_fall]-[pattern:number]',
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
    $interval = new DateInterval('P3M');
    $generated_time->add($interval);
    $current_time->add($interval);

    return $generated_time->format('Y') != $current_time->format('Y');
  }

}
