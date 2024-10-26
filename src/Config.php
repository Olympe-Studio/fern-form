<?php

declare(strict_types=1);

namespace Fern\Form;

if (!defined('ABSPATH')) {
  exit;
}

final class Config {
  /**
   * @var int
   */
  private int $retentionDays;

  /**
   * @var array<string, string>
   */
  private array $formCapabilities;

  /**
   * @param array{
   *     retention_days: int,
   *     form_capabilities: array{
   *         create: string,
   *         read: string,
   *         delete: string
   *     }
   * } $config
   */
  public function __construct(array $config) {
    $this->retentionDays = $config['retention_days'];
    $this->formCapabilities = $config['form_capabilities'];
  }

  /**
   * Get the number of days to retain form submissions.
   *
   * @return int
   */
  public function getRetentionDays(): int {
    return $this->retentionDays;
  }

  /**
   * Get the capabilities for form submissions.
   *
   * @return array<string, string>
   */
  public function getFormCapabilities(): array {
    return $this->formCapabilities;
  }
}
