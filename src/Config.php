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
   * Build a Config from a filtered array, falling back to the defaults for
   * anything the filter left out or returned in the wrong shape.
   *
   * `fern:form:config` is a public filter: a third party may legitimately
   * return only the key it cares about. Reading the array directly turns that
   * into a fatal error at plugin load, which is a poor trade for a config
   * object with two keys.
   *
   * @param array<string, mixed> $config   The filtered configuration.
   * @param array{retention_days: int, form_capabilities: array<string, string>} $defaults
   *
   * @return self
   */
  public static function fromArray(array $config, array $defaults): self {
    $retentionDays = $config['retention_days'] ?? $defaults['retention_days'];
    $capabilities = $config['form_capabilities'] ?? $defaults['form_capabilities'];

    if (!is_array($capabilities)) {
      $capabilities = $defaults['form_capabilities'];
    }

    $safeCapabilities = [];
    foreach ($defaults['form_capabilities'] as $key => $fallback) {
      $value = $capabilities[$key] ?? $fallback;
      $safeCapabilities[$key] = is_string($value) && $value !== '' ? $value : $fallback;
    }

    return new self([
      'retention_days' => is_numeric($retentionDays) ? (int) $retentionDays : $defaults['retention_days'],
      'form_capabilities' => $safeCapabilities,
    ]);
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
